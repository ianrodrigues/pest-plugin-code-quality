<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Support;

use Pest\TestSuite;

final class ProjectPath
{
    /**
     * Matches how the rest of Pest's architecture output reads: relative to
     * the project root, with forward slashes on every platform.
     */
    public static function relative(string $path): string
    {
        $root = TestSuite::getInstance()->rootPath;

        if ($root !== '' && str_starts_with($path, $root.DIRECTORY_SEPARATOR)) {
            $path = substr($path, strlen($root) + 1);
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    public static function canonical(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }
}
