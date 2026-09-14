<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Policies;

use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Baseline\PolicyBaseline;
use IanRodrigues\CodeQuality\Selection\Coverage;

final readonly class PolicyResult
{
    /**
     * Every violation is carried, never only the first. `$measurements`
     * carries every method the metric applied to, not only offenders, so
     * inspection tooling can show a full picture of what was measured.
     *
     * @param list<Violation> $violations
     * @param list<MethodMeasurements> $measurements
     */
    public function __construct(
        public Policy $policy,
        public array $violations,
        public int $objectsSeen,
        public int $methodsMeasured,
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
