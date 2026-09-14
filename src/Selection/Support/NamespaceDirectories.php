<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Selection\Support;

use Pest\Arch\Support\Composer;

/**
 * Ports `Pest\Arch\Repositories\ObjectsRepository::directoriesByNamespace()`,
 * which is private, so completeness accounting resolves a target's
 * directories the same way Pest's own discovery does instead of guessing.
 */
final class NamespaceDirectories
{
    private function __construct()
    {
    }

    /**
     * @return list<ResolvedDirectory>
     */
    public static function resolve(string $target): array
    {
        $resolved = [];

        foreach (self::prefixes() as $prefix => $directories) {
            // Matches Pest's own boundary-naive prefix check: a composer
            // PSR-4 prefix is a root, never a sibling namespace, so the
            // missing segment boundary here mirrors upstream on purpose.
            if (! str_starts_with($target, $prefix)) {
                continue;
            }

            $resolved = [...$resolved, ...self::resolveWithinPrefix($target, $prefix, $directories)];
        }

        return self::deduplicated($resolved);
    }

    /**
     * A narrower composer PSR-4 root nested inside a broader one (both
     * matching the same target) can resolve to the same physical
     * directory twice; only one accounting entry should exist for it.
     *
     * @param list<ResolvedDirectory> $directories
     * @return list<ResolvedDirectory>
     */
    private static function deduplicated(array $directories): array
    {
        $seen = [];

        foreach ($directories as $directory) {
            $seen[$directory->path] ??= $directory;
        }

        return array_values($seen);
    }

    /**
     * @param list<string> $directories
     * @return list<ResolvedDirectory>
     */
    private static function resolveWithinPrefix(string $target, string $prefix, array $directories): array
    {
        $posFirstPrefix = strpos($target, $prefix);
        $nameWithoutPrefix = $posFirstPrefix !== false ? substr($target, $posFirstPrefix + strlen($prefix)) : $target;
        $subPath = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($nameWithoutPrefix, '\\'));
        $namespace = $subPath === '' ? $prefix : $prefix.'\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $subPath);

        $resolved = [];

        foreach ($directories as $directory) {
            $directory = rtrim($directory, '/\\');
            $fileOrDirectory = $subPath === '' ? $directory : $directory.DIRECTORY_SEPARATOR.$subPath;

            if (is_dir($fileOrDirectory)) {
                $resolved[] = ResolvedDirectory::directory(self::canonical($fileOrDirectory), $namespace);

                if (file_exists($fileOrDirectory.'.php')) {
                    $resolved[] = ResolvedDirectory::file(self::canonical($fileOrDirectory.'.php'), self::parentNamespace($namespace));
                }

                continue;
            }

            if (str_contains($fileOrDirectory, '*')) {
                continue;
            }

            if (file_exists($fileOrDirectory.'.php')) {
                $resolved[] = ResolvedDirectory::file(self::canonical($fileOrDirectory.'.php'), self::parentNamespace($namespace));
            }
        }

        return $resolved;
    }

    private static function canonical(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }

    private static function parentNamespace(string $namespace): string
    {
        $segments = explode('\\', $namespace);
        array_pop($segments);

        return implode('\\', $segments);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function prefixes(): array
    {
        $prefixes = [];

        foreach (Composer::loader()->getPrefixesPsr4() as $namespace => $directories) {
            $prefixes[rtrim($namespace, '\\')] = $directories;
        }

        return $prefixes;
    }
}
