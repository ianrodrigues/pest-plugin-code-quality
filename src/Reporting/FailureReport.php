<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Reporting;

use IanRodrigues\CodeQuality\Analysis\Contribution;
use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Metrics\Metric;
use IanRodrigues\CodeQuality\Policies\Policy;
use IanRodrigues\CodeQuality\Policies\PolicyResult;
use IanRodrigues\CodeQuality\Policies\Violation;

final class FailureReport
{
    /**
     * Beyond this many violations the message stops listing them and
     * points at the JSON report instead of growing without bound.
     */
    public const int TRUNCATION_LIMIT = 20;

    /**
     * Beyond this many listed declarations or parents the block points at
     * the JSON report, which carries the full list. `ccn2` groups by
     * construct instead, so it never grows past the number of kinds.
     */
    public const int COUNTED_LIMIT = 10;

    public static function for(PolicyResult $result): string
    {
        $violations = $result->sortedViolations();
        $identifiers = self::longestVariableIdentifiers($result);

        $blocks = implode("\n\n", array_map(
            static fn (Violation $violation): string => self::block($result->policy, $violation, $identifiers[$violation->symbol] ?? null),
            array_slice($violations, 0, self::TRUNCATION_LIMIT),
        ));

        return $blocks."\n\n".self::summary($result->policy, count($violations));
    }

    /**
     * `variableName`'s failure names the longest identifier itself,
     * alongside the length `$violation->value` already carries; every
     * other metric's value is self-explanatory.
     *
     * @return array<string, string>
     */
    private static function longestVariableIdentifiers(PolicyResult $result): array
    {
        if ($result->policy->metric !== Metric::VariableName) {
            return [];
        }

        $identifiers = [];

        foreach ($result->measurements as $measurement) {
            if ($measurement instanceof MethodMeasurements && $measurement->longestVariableIdentifier !== null) {
                $identifiers[$measurement->symbol] = $measurement->longestVariableIdentifier;
            }
        }

        return $identifiers;
    }

    private static function summary(Policy $policy, int $total): string
    {
        $scope = $policy->metric->scope();

        $line = $total === 1
            ? "1 {$scope->value} exceeds the limit"
            : "{$total} {$scope->plural()} exceed the limit";

        if ($total <= self::TRUNCATION_LIMIT) {
            return $line;
        }

        return $line."\n".sprintf(
            'Showing %d of %d. The full list is available in the JSON report.',
            self::TRUNCATION_LIMIT,
            $total,
        );
    }

    private static function block(Policy $policy, Violation $violation, ?string $identifier): string
    {
        $lines = [
            $violation->symbol,
            "{$violation->path}:{$violation->line}",
            '',
            sprintf('%s (%s): %d', $policy->description, $policy->metricLabel(), $violation->value),
        ];

        if ($identifier !== null) {
            $lines[] = sprintf('Longest: $%s (%d)', $identifier, $violation->value);
        }

        $lines[] = "Allowed: at most {$violation->limit}";

        if ($violation->accepted !== null) {
            $lines[] = "Accepted: {$violation->accepted}";
            $lines[] = 'Increase: '.$violation->increase();
        }

        $lines[] = 'Exceeded by: '.$violation->excess();

        foreach (self::countedLines($violation) as $line) {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private static function countedLines(Violation $violation): array
    {
        $items = match ($violation->metric) {
            Metric::Ccn2 => self::groupedByLabel($violation->contributions),
            Metric::Methods,
            Metric::Properties,
            Metric::Inheritance => self::capped(array_map(self::labelWithLine(...), $violation->contributions)),
            Metric::Lines,
            Metric::Params,
            Metric::ClassLines,
            Metric::ClassName,
            Metric::MethodName,
            Metric::VariableName => [],
        };

        if ($items === []) {
            return [];
        }

        return ['Counted:', ...array_map(static fn (string $item): string => '  '.$item, $items)];
    }

    /**
     * Most frequent construct first, then the one that appears first.
     *
     * @param list<Contribution> $contributions
     * @return list<string>
     */
    private static function groupedByLabel(array $contributions): array
    {
        $groups = [];

        foreach ($contributions as $contribution) {
            $groups[$contribution->label][] = $contribution->line ?? 0;
        }

        $labels = array_keys($groups);

        usort($labels, static fn (string $a, string $b): int => [-count($groups[$a]), min($groups[$a])] <=> [-count($groups[$b]), min($groups[$b])]);

        return array_map(static function (string $label) use ($groups): string {
            $lines = $groups[$label];
            sort($lines);

            return sprintf(
                '%d x %s (%s %s)',
                count($lines),
                $label,
                count($lines) === 1 ? 'line' : 'lines',
                implode(', ', $lines),
            );
        }, $labels);
    }

    private static function labelWithLine(Contribution $contribution): string
    {
        return $contribution->line === null
            ? $contribution->label
            : "{$contribution->label} (line {$contribution->line})";
    }

    /**
     * @param list<string> $items
     * @return list<string>
     */
    private static function capped(array $items): array
    {
        $hidden = count($items) - self::COUNTED_LIMIT;

        if ($hidden <= 0) {
            return $items;
        }

        return [
            ...array_slice($items, 0, self::COUNTED_LIMIT),
            sprintf('... and %d more (see the JSON report)', $hidden),
        ];
    }
}
