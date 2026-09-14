<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Selection;

/**
 * Process-wide, resettable switch for how skipped files are treated. The
 * seam a future `--quality-strict` CLI option hangs off, not a CLI option
 * itself.
 */
final class Config
{
    private static bool $strict = false;

    private function __construct()
    {
    }

    public static function strict(bool $strict = true): void
    {
        self::$strict = $strict;
    }

    public static function isStrict(): bool
    {
        return self::$strict;
    }

    public static function reset(): void
    {
        self::$strict = false;
    }
}
