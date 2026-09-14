<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Support;

use Pest\TestSuite;

final class ProjectPath
{
    /**
     * Matches how the rest of Pest's architecture output reads: relative to
     * the project root, with forward slashes on every platform. Normalised
     * unconditionally rather than through `DIRECTORY_SEPARATOR`, so a
     * Windows-style path is read the same way on every host OS a test runs
     * on. A path outside the root is returned normalised but otherwise
     * unchanged, since it cannot be made root-relative.
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
