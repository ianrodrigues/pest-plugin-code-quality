<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Support;

use Pest\TestSuite;

final class ProjectPath
{
    /**
     * Relative to the project root with forward slashes, matching Pest's
     * own architecture output; normalised unconditionally so it reads the
     * same on every host OS. A path outside the root is returned unchanged.
     */
    public static function relative(string $path): string
    {
        $root = self::normalise(TestSuite::getInstance()->rootPath);
        $path = self::normalise($path);

        if ($root !== '' && str_starts_with($path, $root.'/')) {
            return substr($path, strlen($root) + 1);
        }

        return $path;
    }

    public static function canonical(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }

    private static function normalise(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
