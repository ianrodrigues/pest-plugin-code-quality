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
     * `$measurements` carries every symbol the metric applied to, not just
     * the offenders, so inspection tooling sees the full picture. One of
     * `$methodsMeasured`/`$classesMeasured` is always zero, per metric scope.
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
