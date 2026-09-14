<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Performance;

use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Installs a generated performance project through a path repository and
 * keeps its installed copy of this package in sync, the same way
 * `tests/Support/FixtureProject` does for the fixture app under
 * `tests/Fixtures` — except the project directory here is chosen by the
 * caller (typically a temp directory) rather than fixed on disk.
 */
final class PerfProject
{
    private function __construct()
    {
    }

    public static function install(string $path): void
    {
        $process = new Process(['composer', 'install', '--no-interaction', '--no-progress', '--quiet'], $path);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Performance project \"composer install\" failed:\n".$process->getOutput().$process->getErrorOutput(),
            );
        }
    }

    /**
     * The path repository copies rather than symlinks, so the installed
     * copy of this package would otherwise still be whatever `composer
     * install` found the moment it first ran, never the source tree this
     * benchmark is meant to measure.
     */
    public static function sync(string $path): void
    {
        $installed = $path.'/vendor/ianrodrigues/pest-plugin-code-quality';
        $source = dirname(__DIR__, 2);

        foreach (['src', 'schema'] as $directory) {
            self::mirror($source.'/'.$directory, $installed.'/'.$directory);
        }
    }

    private static function mirror(string $from, string $to): void
    {
        foreach (Finder::create()->files()->in($from) as $file) {
            $target = $to.'/'.$file->getRelativePathname();

            if (is_file($target) && filemtime($target) >= $file->getMTime()) {
                continue;
            }

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }

            copy($file->getPathname(), $target);
        }

        foreach (Finder::create()->files()->in($to) as $file) {
            if (! is_file($from.'/'.$file->getRelativePathname())) {
                unlink($file->getPathname());
            }
        }
    }
}
