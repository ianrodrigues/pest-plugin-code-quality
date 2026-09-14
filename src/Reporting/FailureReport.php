<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Reporting;

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

    public static function for(PolicyResult $result): string
    {
        $violations = $result->sortedViolations();

        $blocks = implode("\n\n", array_map(
            static fn (Violation $violation): string => self::block($result->policy, $violation),
            array_slice($violations, 0, self::TRUNCATION_LIMIT),
        ));

        return $blocks."\n\n".self::summary(count($violations));
    }

    private static function summary(int $total): string
    {
        $line = $total === 1
            ? '1 method exceeds the limit'
            : "{$total} methods exceed the limit";

        if ($total <= self::TRUNCATION_LIMIT) {
            return $line;
        }

        return $line."\n".sprintf(
            'Showing %d of %d. The full list is available in the JSON report.',
            self::TRUNCATION_LIMIT,
            $total,
        );
    }

    private static function block(Policy $policy, Violation $violation): string
    {
        $lines = [
            $violation->symbol,
            "{$violation->path}:{$violation->line}",
            '',
            sprintf('%s (%s): %d', $policy->description, $policy->metricLabel(), $violation->value),
            "Allowed: at most {$violation->limit}",
        ];

        if ($violation->accepted !== null) {
            $lines[] = "Accepted: {$violation->accepted}";
            $lines[] = 'Increase: '.$violation->increase();
        }

        $lines[] = 'Exceeded by: '.$violation->excess();

        return implode("\n", $lines);
    }
}
