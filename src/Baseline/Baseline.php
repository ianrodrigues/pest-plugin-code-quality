<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Metrics\Metric;

/**
 * The accepted excesses of a whole project, as read from — or about to be
 * written to — a baseline file.
 */
final readonly class Baseline
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<BaselineEntry> $entries
     */
    private function __construct(
        public array $entries,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Sorted by policy, then symbol, so two runs over the same codebase
     * produce byte-identical files whatever order the policies ran in.
     *
     * @param list<BaselineEntry> $entries
     */
    public static function of(array $entries): self
    {
        usort(
            $entries,
            static fn (BaselineEntry $a, BaselineEntry $b): int => [$a->policy, $a->symbol] <=> [$b->policy, $b->symbol],
        );

        return new self($entries);
    }

    /**
     * @return list<BaselineEntry>
     */
    public function forPolicy(string $policy): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (BaselineEntry $entry): bool => $entry->policy === $policy,
        ));
    }

    /**
     * Every entry declared by a policy that did not take part in this run,
     * kept untouched so a filtered run never drops allowances it never
     * looked at.
     *
     * @param list<string> $policies
     * @return list<BaselineEntry>
     */
    public function exceptPolicies(array $policies): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (BaselineEntry $entry): bool => ! in_array($entry->policy, $policies, true),
        ));
    }

    public function match(string $policy, Metric $metric, int $limit, string $path = ''): PolicyBaseline
    {
        $accepted = [];
        $stale = [];

        foreach ($this->forPolicy($policy) as $entry) {
            if ($entry->appliesTo($metric, $limit)) {
                $accepted[$entry->symbol] = $entry->accepted;

                continue;
            }

            $stale[] = $entry;
        }

        return new PolicyBaseline($accepted, $stale, $path);
    }
}
