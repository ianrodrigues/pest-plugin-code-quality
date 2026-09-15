<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Support;

use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * A throwaway Pest project under `tests/Fixtures`, installed once per
 * suite run since `composer install` is expensive there. `Project` is
 * read-only and shared; `Adoption` is rewritten by baseline tests.
 */
final class FixtureProject
{
    /** @var array<string, true> */
    private static array $ensured = [];

    private function __construct()
    {
    }

    public static function path(string $project = 'Project'): string
    {
        return __DIR__.'/../Fixtures/'.$project;
    }

    public static function ensureInstalled(string $project = 'Project'): string
    {
        $path = self::path($project);

        if (! isset(self::$ensured[$project])) {
            if (! is_dir($path.'/vendor')) {
                self::install($path);
            }

            self::sync($path);

            self::$ensured[$project] = true;
        }

        return $path;
    }

    /**
     * @param list<string> $arguments arguments after `vendor/bin/pest`
     * @return array{exitCode: int, output: string}
     */
    public static function runPest(array $arguments, string $project = 'Project'): array
    {
        $path = self::ensureInstalled($project);

        $process = new Process([...[PHP_BINARY, 'vendor/bin/pest'], ...$arguments], $path, [
            ...self::inheritedParallelStateToClear(),
            'XDEBUG_MODE' => 'off',
        ]);
        $process->setTimeout(120);
        $process->run();

        return [
            'exitCode' => $process->getExitCode() ?? -1,
            'output' => $process->getOutput().$process->getErrorOutput(),
        ];
    }

    /**
     * Clears this suite's own `--parallel` env vars so the fixture's pest
     * does not believe itself already a worker; `Symfony\Process` drops a
     * var from the child when set to `false`, rather than inheriting it.
     *
     * @return array<string, false>
     */
    private static function inheritedParallelStateToClear(): array
    {
        $cleared = ['PARATEST' => false, 'TEST_TOKEN' => false, 'UNIQUE_TEST_TOKEN' => false];

        foreach ([$_SERVER, $_ENV] as $source) {
            foreach ($source as $key => $value) {
                if (is_string($key) && str_starts_with($key, 'PEST_PARALLEL_GLOBAL_')) {
                    $cleared[$key] = false;
                }
            }
        }

        return $cleared;
    }

    /**
     * The fixture requires this package through a path repository that
     * copies rather than symlinks, so the installed copy would otherwise
     * stay whatever `composer install` found on the first run.
     */
    private static function sync(string $path): void
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

    private static function install(string $path): void
    {
        $process = new Process(['composer', 'install', '--no-interaction', '--no-progress', '--quiet'], $path);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Fixture project \"composer install\" failed:\n".$process->getOutput().$process->getErrorOutput(),
            );
        }
    }
}
