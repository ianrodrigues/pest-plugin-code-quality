<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Policies\Policy;

/**
 * The stable name a baseline entry belongs to: the file, line and limit
 * a policy is declared with take no part, so moving a test or changing
 * its limit never orphans the entry — only marks it stale.
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
        $discriminator = $policy->ignoringAccessors ? self::SEPARATOR.'ignoringAccessors' : '';

        if (self::isWritten($description, $policy)) {
            return $description.self::SEPARATOR.$policy->metric->value.$discriminator;
        }

        sort($targets);

        return '['.implode(', ', $targets).']'.self::SEPARATOR.$policy->expectation.$discriminator;
    }

    /**
     * A description-less `arch()` chain still gets one, built by Pest from
     * the chain itself; since no flag survives past declaration time, its
     * shape — ending in this policy's expectation — is what gives it away.
     */
    private static function isWritten(string $description, Policy $policy): bool
    {
        if ($description === '' || $description === 'unknown') {
            return false;
        }

        return ! str_contains($description, self::CHAIN_SEPARATOR.$policy->expectation);
    }
}
