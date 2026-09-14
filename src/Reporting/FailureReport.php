<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Reporting;

use Rdgs\PestCodeQuality\Policies\Policy;
use Rdgs\PestCodeQuality\Policies\PolicyResult;
use Rdgs\PestCodeQuality\Policies\Violation;

final class FailureReport
{
    public static function for(PolicyResult $result): string
    {
        return implode("\n\n", array_map(
            static fn (Violation $violation): string => self::block($result->policy, $violation),
            $result->sortedViolations(),
        ));
    }

    private static function block(Policy $policy, Violation $violation): string
    {
        return sprintf(
            "%s\n%s:%d\n\n%s (%s): %d\nAllowed: at most %d\nExceeded by: %d",
            $violation->symbol,
            $violation->path,
            $violation->line,
            $policy->description,
            $policy->metricLabel(),
            $violation->value,
            $violation->limit,
            $violation->excess(),
        );
    }
}
