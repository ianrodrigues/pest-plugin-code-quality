<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Support;

/**
 * Honours the architecture plugin's inline escape hatches where a method is
 * declared, so they work here exactly as on built-in expectations.
 */
final class IgnoredLines
{
    /** @var array<string, list<string>> */
    private static array $files = [];

    public static function has(string $path, int $line): bool
    {
        $contents = self::lines($path);

        $own = $contents[$line - 1] ?? null;

        if ($own !== null && str_contains($own, '@pest-arch-ignore-line')) {
            return true;
        }

        $previous = $contents[$line - 2] ?? null;

        return $previous !== null && str_contains($previous, '@pest-arch-ignore-next-line');
    }

    /**
     * @return list<string>
     */
    private static function lines(string $path): array
    {
        if (isset(self::$files[$path])) {
            return self::$files[$path];
        }

        $contents = @file($path);

        return self::$files[$path] = $contents === false ? [] : $contents;
    }
}
