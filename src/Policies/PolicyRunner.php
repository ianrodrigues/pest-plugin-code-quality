<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Policies;

use Pest\Arch\Options\LayerOptions;
use Pest\Arch\Support\AssertLocker;
use PhpParser\Node\Stmt;
use PHPUnit\Architecture\Elements\ObjectDescription;
use Rdgs\PestCodeQuality\Analysis\AnalysisError;
use Rdgs\PestCodeQuality\Analysis\AstMeasurer;
use Rdgs\PestCodeQuality\Analysis\FileMeasurements;
use Rdgs\PestCodeQuality\Analysis\MeasurementCache;
use Rdgs\PestCodeQuality\Selection\Config;
use Rdgs\PestCodeQuality\Selection\Coverage;
use Rdgs\PestCodeQuality\Selection\EmptySelection;
use Rdgs\PestCodeQuality\Selection\Scanner;
use Rdgs\PestCodeQuality\Selection\SkippedFilesFound;
use Rdgs\PestCodeQuality\Selection\TargetCoverage;
use Rdgs\PestCodeQuality\Selection\WarningsCollector;
use Rdgs\PestCodeQuality\Support\IgnoredLines;
use Rdgs\PestCodeQuality\Support\ProjectPath;

/**
 * Walks the whole layer rather than stopping at the first offender, so one
 * failure can report every offending method.
 */
final class PolicyRunner
{
    private static ?self $instance = null;

    private readonly Scanner $scanner;

    public function __construct(
        private readonly AstMeasurer $measurer = new AstMeasurer(),
        private readonly MeasurementCache $cache = new MeasurementCache(),
        ?Scanner $scanner = null,
    ) {
        $this->scanner = $scanner ?? new Scanner($this->measurer, $this->cache);
    }

    /**
     * Shared so several policies over the same target reuse one set of
     * measurements instead of measuring each file again.
     */
    public static function shared(): self
    {
        return self::$instance ??= new self();
    }

    public function run(Policy $policy, Targets $targets, LayerOptions $options): PolicyResult
    {
        AssertLocker::incrementAndLock();

        try {
            return $this->runLocked($policy, $targets, $options);
        } finally {
            AssertLocker::unlock();
        }
    }

    private function runLocked(Policy $policy, Targets $targets, LayerOptions $options): PolicyResult
    {
        /** @var list<array{0: list<ObjectDescription>, 1: TargetCoverage}> $perTarget */
        $perTarget = [];

        foreach ($targets->values() as $target) {
            $objects = $targets->resolveTarget($options, $target);
            $coverage = $this->scanner->scan($target, $objects, $policy);

            if ($coverage->isEmpty() && ! $policy->allowEmpty) {
                throw EmptySelection::for($target, $coverage);
            }

            $perTarget[] = [$objects, $coverage];
        }

        $coverage = new Coverage(array_map(
            static fn (array $entry): TargetCoverage => $entry[1],
            $perTarget,
        ));

        $this->handleSkipped($coverage);

        [$violations, $objectsSeen, $methodsMeasured] = $this->measure($policy, $perTarget);

        return new PolicyResult($policy, $violations, $objectsSeen, $methodsMeasured, $coverage);
    }

    private function handleSkipped(Coverage $coverage): void
    {
        $skipped = $coverage->skippedFiles();

        if ($skipped === []) {
            return;
        }

        if (Config::isStrict()) {
            throw SkippedFilesFound::for($skipped);
        }

        WarningsCollector::record($skipped);
    }

    /**
     * @param list<array{0: list<ObjectDescription>, 1: TargetCoverage}> $perTarget
     * @return array{0: list<Violation>, 1: int, 2: int}
     */
    private function measure(Policy $policy, array $perTarget): array
    {
        $violations = [];
        $objectsSeen = 0;
        $methodsMeasured = 0;
        $measured = [];

        foreach ($perTarget as [$objects]) {
            foreach ($objects as $object) {
                $stmts = $this->statements($object);

                if ($stmts === null) {
                    continue;
                }

                $path = ProjectPath::canonical($object->path);

                if (isset($measured[$path])) {
                    continue;
                }

                $measured[$path] = true;
                $objectsSeen++;

                foreach ($this->measureFile($path, $stmts) as $method) {
                    if ($method->isAnonymous()) {
                        continue;
                    }

                    $value = $policy->valueFor($method);

                    if ($value === null) {
                        continue;
                    }

                    $methodsMeasured++;

                    if ($policy->allows($value) || IgnoredLines::has($path, $method->line)) {
                        continue;
                    }

                    $violations[] = new Violation(
                        $method->symbol,
                        ProjectPath::relative($path),
                        $method->line,
                        $policy->metric,
                        $value,
                        $policy->limit,
                    );
                }
            }
        }

        return [$violations, $objectsSeen, $methodsMeasured];
    }

    /**
     * Null for descriptions that carry no syntax tree, such as vendor
     * classes and global functions.
     *
     * @return list<Stmt>|null
     */
    private function statements(ObjectDescription $object): ?array
    {
        if (! isset($object->stmts) || $object->stmts === []) {
            return null;
        }

        /** @var list<Stmt> $stmts */
        $stmts = array_values($object->stmts);

        return $stmts;
    }

    /**
     * @param list<Stmt> $stmts
     */
    private function measureFile(string $path, array $stmts): FileMeasurements
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw AnalysisError::unreadable($path);
        }

        return $this->cache->remember(
            $path,
            $contents,
            fn (): FileMeasurements => $this->measurer->measureAst($path, $stmts),
        );
    }
}
