<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

/**
 * Whatever a single policy run may take from the baseline: the ceiling
 * raised per symbol, and the entries that no longer match the policy's
 * metric version or configured limit and are therefore ignored.
 */
final readonly class PolicyBaseline
{
    /**
     * @param array<string, int> $accepted
     * @param list<BaselineEntry> $stale
     */
    public function __construct(
        public array $accepted,
        public array $stale,
        public string $path = '',
    ) {
    }

    public function acceptedFor(string $symbol): ?int
    {
        return $this->accepted[$symbol] ?? null;
    }
}
