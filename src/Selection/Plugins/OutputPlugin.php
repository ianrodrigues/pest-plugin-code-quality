<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection\Plugins;

use IanRodrigues\CodeQuality\Baseline\BaselineCommand;
use IanRodrigues\CodeQuality\Baseline\BaselineMode;
use IanRodrigues\CodeQuality\Baseline\BaselineRepository;
use IanRodrigues\CodeQuality\Config;
use IanRodrigues\CodeQuality\Reporting\QualityReport;
use IanRodrigues\CodeQuality\Reporting\RunRecorder;
use IanRodrigues\CodeQuality\Selection\SkippedFile;
use IanRodrigues\CodeQuality\Selection\WarningsCollector;
use JsonException;
use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Contracts\Plugins\Terminable;
use Pest\Plugins\Parallel;
use Pest\TestSuite;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Registered in `composer.json`'s `extra.pest.plugins`. CLI flags never
 * reach a worker under `--parallel` (Pest strips them before spawning
 * it), so they round-trip through `Parallel::setGlobal()`/`getGlobal()`.
 *
 * @phpstan-import-type PolicyEntry from RunRecorder
 */
final class OutputPlugin implements AddsOutput, HandlesArguments, Terminable
{
    private const int LINE_CAP = 10;

    private const string INSPECT_GLOBAL = 'QUALITY_INSPECT';

    private const string INSPECT_PATH_GLOBAL = 'QUALITY_INSPECT_PATH';

    private const string JSON_PATH_GLOBAL = 'QUALITY_JSON_PATH';

    private const string BASELINE_PATH_GLOBAL = 'QUALITY_BASELINE_PATH';

    private const string BASELINE_MODE_GLOBAL = 'QUALITY_BASELINE_MODE';

    private bool $inspect = false;

    private ?string $inspectPath = null;

    private ?string $jsonPath = null;

    private BaselineMode $mode = BaselineMode::Check;

    private bool $conflicting = false;

    public function __construct(
        private readonly OutputInterface $output,
    ) {
    }

    /**
     * @param list<string> $arguments
     * @return list<string>
     */
    public function handleArguments(array $arguments): array
    {
        [$inspectPresent, $inspectValue, $arguments] = $this->extractOption('quality-inspect', $arguments);
        [$jsonPresent, $jsonValue, $arguments] = $this->extractOption('quality-json', $arguments);
        [$generate, , $arguments] = $this->extractOption('quality-baseline-generate', $arguments);
        [$tighten, , $arguments] = $this->extractOption('quality-baseline-tighten', $arguments);
        [$baselinePresent, $baselineValue, $arguments] = $this->extractOption('quality-baseline', $arguments);

        $requested = $inspectPresent || $jsonPresent || $baselinePresent || $generate || $tighten;

        /*
         * A worker never sees these flags directly; explicit arguments win,
         * falling back to the propagated globals only when local is empty.
         */
        if (! $requested && Parallel::isWorker()) {
            $this->inspect = Parallel::getGlobal(self::INSPECT_GLOBAL) === true;
            $this->inspectPath = $this->nonEmpty(Parallel::getGlobal(self::INSPECT_PATH_GLOBAL));
            $this->jsonPath = $this->nonEmpty(Parallel::getGlobal(self::JSON_PATH_GLOBAL));

            $this->useBaseline(
                $this->nonEmpty(Parallel::getGlobal(self::BASELINE_PATH_GLOBAL)),
                $this->modeNamed($this->nonEmpty(Parallel::getGlobal(self::BASELINE_MODE_GLOBAL))),
            );

            return $arguments;
        }

        $this->inspect = $inspectPresent;
        $this->inspectPath = $inspectValue;
        $this->jsonPath = $jsonPresent ? $jsonValue : null;

        $this->conflicting = $generate && $tighten;

        $this->useBaseline($baselinePresent ? $baselineValue : null, match (true) {
            $this->conflicting => BaselineMode::Check,
            $generate => BaselineMode::Generate,
            $tighten => BaselineMode::Tighten,
            default => BaselineMode::Check,
        });

        Parallel::setGlobal(self::INSPECT_GLOBAL, $inspectPresent);
        Parallel::setGlobal(self::INSPECT_PATH_GLOBAL, $inspectValue ?? '');
        Parallel::setGlobal(self::JSON_PATH_GLOBAL, $this->jsonPath ?? '');
        Parallel::setGlobal(self::BASELINE_PATH_GLOBAL, $baselineValue ?? '');
        Parallel::setGlobal(self::BASELINE_MODE_GLOBAL, $this->mode->name);

        return $arguments;
    }

    public function addOutput(int $exitCode): int
    {
        $this->printSkippedFiles();

        if ($this->conflicting) {
            $this->output->writeln('');
            $this->output->writeln(
                '--quality-baseline-generate and --quality-baseline-tighten cannot be used in the same run.',
            );

            return max($exitCode, 1);
        }

        $writes = $this->mode !== BaselineMode::Check;

        if (! $this->inspect && $this->jsonPath === null && ! $writes && Config::baselinePath() === null) {
            return $exitCode;
        }

        $entries = $this->mergedEntries();
        $baseline = new BaselineCommand($this->output);

        if ($this->inspect || $this->jsonPath !== null) {
            $this->writeReports($entries);
        }

        if (! $writes) {
            $baseline->reportStale($entries);

            return $exitCode;
        }

        return $baseline->write($entries, $this->baselinePath($entries), $this->mode)
            ? $exitCode
            : max($exitCode, 1);
    }

    /**
     * A worker process never calls `addOutput()`; this is its only chance
     * to hand its share of the run log back to the orchestrator.
     */
    public function terminate(): void
    {
        if (! Parallel::isWorker()) {
            return;
        }

        if (! $this->inspect && $this->jsonPath === null && Config::baselinePath() === null) {
            return;
        }

        $entries = RunRecorder::all();

        if ($entries === []) {
            return;
        }

        $directory = $this->partialDirectory();

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $file = $directory.DIRECTORY_SEPARATOR.'worker-'.$this->workerToken().'.json';

        file_put_contents($file, json_encode($entries, JSON_THROW_ON_ERROR));
    }

    /**
     * @param list<PolicyEntry> $entries
     */
    private function writeReports(array $entries): void
    {
        $report = QualityReport::from($entries);

        if ($this->jsonPath !== null) {
            $this->writeJson($this->jsonPath, $report->findingsOnly());
        }

        if (! $this->inspect) {
            return;
        }

        if ($this->inspectPath !== null) {
            $this->writeJson($this->inspectPath, $report->full());

            return;
        }

        $this->output->writeln('');
        $this->output->writeln($report->toConsole());
    }

    private function useBaseline(?string $path, BaselineMode $mode): void
    {
        $this->mode = $mode;

        Config::overrideBaseline($path);
        Config::useBaselineMode($mode);
        BaselineRepository::reset();
    }

    private function modeNamed(?string $name): BaselineMode
    {
        return match ($name) {
            BaselineMode::Generate->name => BaselineMode::Generate,
            BaselineMode::Tighten->name => BaselineMode::Tighten,
            default => BaselineMode::Check,
        };
    }

    /**
     * Under `--parallel`, the process that writes the baseline may never
     * have loaded `tests/Pest.php` itself, so the path a worker resolved is
     * the fallback.
     *
     * @param list<PolicyEntry> $entries
     */
    private function baselinePath(array $entries): ?string
    {
        $path = BaselineRepository::path();

        if ($path !== null) {
            return $path;
        }

        foreach ($entries as $entry) {
            $carried = $entry['baseline']['path'] ?? null;

            if (is_string($carried) && $carried !== '') {
                return BaselineRepository::resolve($carried);
            }
        }

        return null;
    }

    private function printSkippedFiles(): void
    {
        $files = WarningsCollector::all();

        if ($files === []) {
            return;
        }

        $this->output->writeln('');
        $this->output->writeln(sprintf(
            'Quality: %d files were found but not analysed (run with --quality-inspect for details)',
            count($files),
        ));

        foreach (array_slice($files, 0, self::LINE_CAP) as $file) {
            $this->output->writeln($this->line($file));
        }
    }

    /**
     * The orchestrator's own `RunRecorder`, plus whatever every worker
     * persisted on `terminate()`. Partial files are consumed once: deleted
     * as they are folded in, so a later, unrelated run never inherits them.
     *
     * @return list<PolicyEntry>
     */
    private function mergedEntries(): array
    {
        $entries = RunRecorder::all();
        $directory = $this->partialDirectory();

        if (! is_dir($directory)) {
            return $entries;
        }

        foreach (glob($directory.DIRECTORY_SEPARATOR.'*.json') ?: [] as $file) {
            $contents = file_get_contents($file);

            if ($contents !== false) {
                $entries = [...$entries, ...$this->decodeEntries($contents)];
            }

            @unlink($file);
        }

        @rmdir($directory);

        return $entries;
    }

    /**
     * @return list<PolicyEntry>
     */
    private function decodeEntries(string $contents): array
    {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        /** @var list<PolicyEntry> $decoded */
        return $decoded;
    }

    /**
     * @param array<string, mixed> $document
     */
    private function writeJson(string $path, array $document): void
    {
        $directory = dirname($path);

        if ($directory !== '' && $directory !== '.' && ! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        file_put_contents($path, $json."\n");
    }

    /**
     * @param list<string> $arguments
     * @return array{0: bool, 1: string|null, 2: list<string>}
     */
    private function extractOption(string $name, array $arguments): array
    {
        $present = false;
        $value = null;
        $remaining = [];
        $flag = "--{$name}";
        $prefix = "--{$name}=";

        foreach ($arguments as $argument) {
            if ($argument === $flag) {
                $present = true;

                continue;
            }

            if (str_starts_with($argument, $prefix)) {
                $present = true;
                $value = substr($argument, strlen($prefix));

                continue;
            }

            $remaining[] = $argument;
        }

        return [$present, $value, $remaining];
    }

    private function nonEmpty(string|int|bool|null $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function partialDirectory(): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'pest-quality-'.md5(TestSuite::getInstance()->rootPath);
    }

    private function workerToken(): string
    {
        $raw = $_SERVER['UNIQUE_TEST_TOKEN'] ?? $_ENV['UNIQUE_TEST_TOKEN']
            ?? $_SERVER['TEST_TOKEN'] ?? $_ENV['TEST_TOKEN'] ?? null;

        $token = is_scalar($raw) ? (string) $raw : (string) getmypid();
        $sanitised = preg_replace('/[^A-Za-z0-9_-]/', '', $token);

        return $sanitised !== null && $sanitised !== '' ? $sanitised : (string) getmypid();
    }

    private function line(SkippedFile $file): string
    {
        return sprintf('  %s (%s)', $file->path, $file->reason->label());
    }
}
