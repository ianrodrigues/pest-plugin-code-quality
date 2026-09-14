<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Policies\Policy;

/**
 * The stable name a baseline entry belongs to.
 *
 * Neither the file nor the line a policy is declared on takes part, so
 * moving or reordering a test file keeps every entry valid. The limit takes
 * no part either: changing it must mark entries stale, not orphan them.
 */
final class PolicyIdentity
{
    private const string SEPARATOR = ' :: ';

    /**
     * Pest joins the calls of a description-less chain with this arrow to
     * name the test.
     */
    private const string CHAIN_SEPARATOR = ' → ';

    private function __construct()
    {
    }

    /**
     * @param list<string> $targets
     */
    public static function for(array $targets, Policy $policy, string $description): string
    {
        if (self::isWritten($description, $policy)) {
            return $description.self::SEPARATOR.$policy->metric->value;
        }

        sort($targets);

        return '['.implode(', ', $targets).']'.self::SEPARATOR.$policy->expectation;
    }

    /**
     * A description-less `arch()` chain still gets one, built by Pest from
     * the chain itself; since no flag survives past declaration time, the
     * chain's own shape — ending in this policy's expectation — is what
     * gives it away.
     */
    private static function isWritten(string $description, Policy $policy): bool
    {
        if ($description === '' || $description === 'unknown') {
            return false;
        }

        return ! str_contains($description, self::CHAIN_SEPARATOR.$policy->expectation);
    }
}
