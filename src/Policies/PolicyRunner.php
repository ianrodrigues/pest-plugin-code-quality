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
use Rdgs\PestCodeQuality\Support\IgnoredLines;
use Rdgs\PestCodeQuality\Support\ProjectPath;

/**
 * Walks the whole layer rather than stopping at the first offender, so one
 * failure can report every offending method.
 */
final class PolicyRunner
{
    private static ?self $instance = null;

    public function __construct(
        private readonly AstMeasurer $measurer = new AstMeasurer(),
        private readonly MeasurementCache $cache = new MeasurementCache(),
    ) {
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

        $violations = [];
        $objectsSeen = 0;
        $methodsMeasured = 0;
        $measured = [];

        foreach ($targets->resolve($options) as $object) {
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

            foreach ($this->measure($path, $stmts) as $method) {
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

        AssertLocker::unlock();

        return new PolicyResult($policy, $violations, $objectsSeen, $methodsMeasured);
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
    private function measure(string $path, array $stmts): FileMeasurements
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
