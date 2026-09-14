<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality;

use IanRodrigues\CodeQuality\Baseline\BaselineMode;

/**
 * Process-wide, resettable configuration for the plugin: `pest()` is final
 * with no room for a `quality()` accessor, so `tests/Pest.php` configures
 * this instead:
 *
 *     \IanRodrigues\CodeQuality\Config::baseline(__DIR__.'/quality-baseline.json');
 *
 * CLI options are read first and win over whatever that file sets.
 */
final class Config
{
    private static bool $strict = false;

    private static ?string $baseline = null;

    private static ?string $override = null;

    private static BaselineMode $mode = BaselineMode::Check;

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

    public static function baseline(?string $path): void
    {
        self::$baseline = $path;
    }

    public static function baselinePath(): ?string
    {
        return self::$override ?? self::$baseline;
    }

    public static function overrideBaseline(?string $path): void
    {
        self::$override = $path;
    }

    public static function useBaselineMode(BaselineMode $mode): void
    {
        self::$mode = $mode;
    }

    public static function baselineMode(): BaselineMode
    {
        return self::$mode;
    }

    public static function reset(): void
    {
        self::$strict = false;
        self::$baseline = null;
        self::$override = null;
        self::$mode = BaselineMode::Check;
    }
}
