<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Metrics\Metric;
use IanRodrigues\CodeQuality\Reporting\RunRecorder;

/**
 * Builds the baseline a finished run should leave behind. Only policies
 * that actually took part are rewritten: everything else is carried over
 * untouched, so a filtered run never drops allowances it never looked at.
 *
 * @phpstan-import-type PolicyEntry from RunRecorder
 */
final class BaselineUpdater
{
    private function __construct()
    {
    }

    /**
     * @param list<PolicyEntry> $entries
     */
    public static function from(array $entries, Baseline $existing, BaselineMode $mode): BaselineUpdate
    {
        $measured = self::measured($entries);
        $written = [];

        foreach ($measured as $policy) {
            $written = [...$written, ...($mode === BaselineMode::Tighten
                ? $policy->tightened($existing->forPolicy($policy->policy))
                : $policy->aboveLimit())];
        }

        $kept = $existing->exceptPolicies(array_map(
            static fn (MeasuredPolicy $policy): string => $policy->policy,
            $measured,
        ));

        $baseline = Baseline::of([...$kept, ...$written]);

        return new BaselineUpdate($baseline, BaselineDiff::between($existing, $baseline));
    }

    /**
     * Writing allowances off a run that never finished would accept
     * whatever the missing policies would have measured, so a single error
     * stops the write.
     *
     * @param list<PolicyEntry> $entries
     */
    public static function ensureComplete(array $entries): void
    {
        $seen = [];

        foreach ($entries as $entry) {
            $location = "{$entry['location']['file']}:{$entry['location']['line']}";

            if ($entry['errors'] !== []) {
                throw BaselineError::incompleteRun($location, $entry['errors'][0]);
            }

            if (isset($seen[$entry['policy']])) {
                throw DuplicatePolicyIdentity::between($entry['policy'], $seen[$entry['policy']], $location);
            }

            $seen[$entry['policy']] = $location;
        }
    }

    /**
     * @param list<PolicyEntry> $entries
     * @return list<MeasuredPolicy>
     */
    private static function measured(array $entries): array
    {
        $values = [];
        $paths = [];
        $first = [];

        foreach ($entries as $entry) {
            $policy = $entry['policy'];
            $first[$policy] ??= $entry;

            foreach ($entry['measurements'] as $measurement) {
                $value = match (Metric::from($entry['metric']['name'])) {
                    Metric::Ccn2 => $measurement['ccn2'],
                    Metric::Lines => $measurement['lines'],
                    Metric::Params => $measurement['params'],
                };

                if ($value === null) {
                    continue;
                }

                $values[$policy][$measurement['symbol']] = $value;
                $paths[$policy][$measurement['symbol']] = $measurement['path'];
            }
        }

        $measured = [];

        foreach ($first as $policy => $entry) {
            $measured[] = new MeasuredPolicy(
                $entry['policy'],
                Metric::from($entry['metric']['name']),
                $entry['limit'],
                $values[$policy] ?? [],
                $paths[$policy] ?? [],
            );
        }

        return $measured;
    }
}
