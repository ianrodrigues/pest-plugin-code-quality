<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Policies;

use Rdgs\PestCodeQuality\Selection\Coverage;

final readonly class PolicyResult
{
    /**
     * Every violation is carried, never only the first. The counts say how
     * much of the target was actually reached, so reporting and
     * completeness checks can build on them.
     *
     * @param list<Violation> $violations
     */
    public function __construct(
        public Policy $policy,
        public array $violations,
        public int $objectsSeen,
        public int $methodsMeasured,
        public ?Coverage $coverage = null,
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
