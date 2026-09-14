<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Reporting\RunRecorder;
use IanRodrigues\CodeQuality\Support\ProjectPath;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * What `--quality-baseline-generate` and `--quality-baseline-tighten` do
 * once the run is over and every worker's share has been merged back.
 *
 * @phpstan-import-type PolicyEntry from RunRecorder
 */
final readonly class BaselineCommand
{
    private const int LINE_CAP = 20;

    public function __construct(
        private OutputInterface $output,
    ) {
    }

    /**
     * @param list<PolicyEntry> $entries
     */
    public function write(array $entries, ?string $path, BaselineMode $mode): bool
    {
        try {
            $update = $this->update($entries, $path, $mode);
        } catch (RuntimeException $error) {
            $this->output->writeln('');
            $this->output->writeln($error->getMessage());

            return false;
        }

        $this->output->writeln('');
        $this->output->writeln('Quality baseline written: '.ProjectPath::relative((string) $path));
        $this->output->writeln('  '.$update->diff->summary());

        foreach (array_slice($update->diff->lines(), 0, self::LINE_CAP) as $line) {
            $this->output->writeln("  {$line}");
        }

        return true;
    }

    /**
     * @param list<PolicyEntry> $entries
     */
    public function reportStale(array $entries): void
    {
        $stale = [];

        foreach ($entries as $entry) {
            foreach ($entry['baseline']['stale'] ?? [] as $row) {
                $stale[$row['policy']."\0".$row['symbol']] = sprintf(
                    '  %s (%s, %s v%d, stored for limit %d)',
                    $row['symbol'],
                    $row['policy'],
                    $row['metric']['name'],
                    $row['metric']['version'],
                    $row['limit'],
                );
            }
        }

        if ($stale === []) {
            return;
        }

        $summary = count($stale) === 1
            ? '1 entry no longer matches its policy and was ignored'
            : count($stale).' entries no longer match their policy and were ignored';

        $this->output->writeln('');
        $this->output->writeln(
            "Quality baseline: {$summary} (run with --quality-baseline-generate to refresh)",
        );

        foreach (array_slice(array_values($stale), 0, self::LINE_CAP) as $line) {
            $this->output->writeln($line);
        }
    }

    /**
     * @param list<PolicyEntry> $entries
     */
    private function update(array $entries, ?string $path, BaselineMode $mode): BaselineUpdate
    {
        if ($path === null) {
            throw BaselineError::notConfigured();
        }

        BaselineUpdater::ensureComplete($entries);

        if (! is_file($path) && $mode === BaselineMode::Tighten) {
            throw BaselineError::missing($path);
        }

        $existing = is_file($path) ? BaselineFile::read($path) : Baseline::empty();

        $update = BaselineUpdater::from($entries, $existing, $mode);

        BaselineFile::write($path, $update->baseline);

        return $update;
    }
}
