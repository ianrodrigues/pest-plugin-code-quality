<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use RuntimeException;

/**
 * Two policies under one name would share each other's allowances, so the
 * run stops and names both declaration sites instead of guessing which one
 * an entry belongs to.
 */
final class DuplicatePolicyIdentity extends RuntimeException
{
    public static function between(string $identity, string $first, string $second): self
    {
        return new self(sprintf(
            "Two quality policies resolve to the same identity \"%s\":\n  %s\n  %s\n"
            .'Give the tests different descriptions so their baseline entries stay apart.',
            $identity,
            $first,
            $second,
        ));
    }
}
