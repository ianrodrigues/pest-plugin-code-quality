<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Metrics\Metric;

/**
 * What one policy measured across a whole run, gathered from the run log
 * rather than from the policy objects themselves: under `--parallel` the
 * process that writes the baseline never ran a policy of its own.
 */
final readonly class MeasuredPolicy
{
    /**
     * @param array<string, int> $values measured value per symbol
     * @param array<string, string> $paths declaring file per symbol
     */
    public function __construct(
        public string $policy,
        public Metric $metric,
        public int $limit,
        public array $values,
        public array $paths,
    ) {
    }

    /**
     * Existing entries lowered to what this run measured: a value never
     * rises here, an entry that no longer matches or falls within the
     * limit is dropped, and a symbol with no existing entry gains none.
     *
     * @param list<BaselineEntry> $entries
     * @return list<BaselineEntry>
     */
    public function tightened(array $entries): array
    {
        $kept = [];

        foreach ($entries as $entry) {
            if (! $entry->appliesTo($this->metric, $this->limit)) {
                $kept[] = $entry;

                continue;
            }

            $value = $this->values[$entry->symbol] ?? null;

            if ($value === null || $value <= $this->limit) {
                continue;
            }

            $kept[] = $entry->with(min($entry->accepted, $value));
        }

        return $kept;
    }

    /**
     * @return list<BaselineEntry>
     */
    public function aboveLimit(): array
    {
        $entries = [];

        foreach ($this->values as $symbol => $value) {
            if ($value <= $this->limit) {
                continue;
            }

            $entries[] = new BaselineEntry(
                $this->policy,
                $symbol,
                $this->metric,
                $this->metric->version(),
                $this->limit,
                $value,
                $this->paths[$symbol] ?? '',
            );
        }

        return $entries;
    }
}
