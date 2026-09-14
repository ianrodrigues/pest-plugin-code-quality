<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use RuntimeException;

/**
 * A baseline that cannot be read or written is an engine problem, not a
 * policy violation, so this stays a plain `RuntimeException`: the owning
 * test ends as an error, never a failure.
 */
final class BaselineError extends RuntimeException
{
    public static function missing(string $path): self
    {
        return new self(
            "The configured quality baseline \"{$path}\" does not exist.\n"
            .'Run with --quality-baseline-generate to create it.',
        );
    }

    public static function malformed(string $path, string $reason): self
    {
        return new self("The quality baseline \"{$path}\" could not be read: {$reason}.");
    }

    public static function incompleteRun(string $location, string $reason): self
    {
        return new self(
            "The quality baseline was not written: a policy did not finish.\n"
            ."  {$location}: {$reason}\n"
            .'Fix the error, then run the baseline command again.',
        );
    }

    public static function notConfigured(): self
    {
        return new self(
            "No quality baseline is configured.\n"
            .'Pass --quality-baseline=path, or call \IanRodrigues\CodeQuality\Config::baseline() in tests/Pest.php.',
        );
    }

    public static function unwritable(string $path): self
    {
        return new self("The quality baseline \"{$path}\" could not be written; the file on disk is unchanged.");
    }
}
