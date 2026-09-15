<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Policies;

use IanRodrigues\CodeQuality\Analysis\ClassMeasurements;
use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Baseline\PolicyBaseline;
use IanRodrigues\CodeQuality\Selection\Coverage;

final readonly class PolicyResult
{
    /**
     * Every violation is carried, never only the first. `$measurements`
     * carries every symbol the metric applied to, not only offenders, so
     * inspection tooling can show a full picture of what was measured. One
     * of `$methodsMeasured` and `$classesMeasured` is always zero: a
     * policy measures the scope of its own metric.
     *
     * @param list<Violation> $violations
     * @param list<ClassMeasurements|MethodMeasurements> $measurements
     */
    public function __construct(
        public Policy $policy,
        public array $violations,
        public int $objectsSeen,
        public int $methodsMeasured,
        public int $classesMeasured = 0,
        public ?Coverage $coverage = null,
        public array $measurements = [],
        public string $identity = '',
        public ?PolicyBaseline $baseline = null,
    ) {
    }

    public function passed(): bool
    {
        return $this->violations === [];
    }

    /**
     * @return list<Violation>
     */
    public function sortedViolations(): array
    {
        $violations = $this->violations;

        usort(
            $violations,
            static fn (Violation $a, Violation $b): int => [$a->path, $a->symbol] <=> [$b->path, $b->symbol],
        );

        return $violations;
    }
}
