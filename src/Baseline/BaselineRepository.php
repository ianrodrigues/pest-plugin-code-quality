<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Config;
use IanRodrigues\CodeQuality\Metrics\Metric;
use Pest\TestSuite;

/**
 * The configured baseline, read from disk once per process and shared by
 * every policy that runs in it.
 */
final class BaselineRepository
{
    private static ?string $loadedFrom = null;

    private static ?Baseline $loaded = null;

    private function __construct()
    {
    }

    /**
     * Null when no baseline is configured at all, which is what a project
     * that has never adopted one looks like.
     */
    public static function path(): ?string
    {
        $path = Config::baselinePath();

        if ($path === null || $path === '') {
            return null;
        }

        return self::resolve($path);
    }

    public static function current(): ?Baseline
    {
        $path = self::path();

        if ($path === null) {
            return null;
        }

        if (self::$loadedFrom === $path && self::$loaded instanceof Baseline) {
            return self::$loaded;
        }

        $baseline = self::load($path);

        self::$loadedFrom = $path;
        self::$loaded = $baseline;

        return $baseline;
    }

    public static function forPolicy(string $policy, Metric $metric, int $limit): ?PolicyBaseline
    {
        return self::current()?->match($policy, $metric, $limit, self::path() ?? '');
    }

    public static function reset(): void
    {
        self::$loadedFrom = null;
        self::$loaded = null;
    }

    /**
     * A file that does not exist yet is only tolerated while generating,
     * which is exactly the run that creates it.
     */
    private static function load(string $path): Baseline
    {
        if (! is_file($path)) {
            if (Config::baselineMode() === BaselineMode::Generate) {
                return Baseline::empty();
            }

            throw BaselineError::missing($path);
        }

        return BaselineFile::read($path);
    }

    /**
     * A path configured relative to the project root resolves against it,
     * so the same string means the same file from any working directory.
     */
    public static function resolve(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1) {
            return $path;
        }

        $root = TestSuite::getInstance()->rootPath;

        return $root === '' ? $path : $root.DIRECTORY_SEPARATOR.$path;
    }
}
