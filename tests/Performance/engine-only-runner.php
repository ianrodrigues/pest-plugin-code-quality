<?php

declare(strict_types=1);

/**
 * Measures every generated file with `AstMeasurer` alone, in one process,
 * isolated from Pest's own startup cost so `bench.php` can time and
 * `/usr/bin/time`-wrap it the same way it does the end-to-end scenarios.
 *
 * Usage: php engine-only-runner.php <package-root> <project-directory>
 */

use IanRodrigues\CodeQuality\Analysis\AstMeasurer;
use Symfony\Component\Finder\Finder;

/** @var list<string> $argv */
[, $packageRoot, $projectDirectory] = $argv;

require $packageRoot.'/vendor/autoload.php';

$measurer = new AstMeasurer();
$measured = 0;

foreach (Finder::create()->files()->in($projectDirectory.'/app')->name('*.php') as $file) {
    $measurer->measure($file->getPathname());
    $measured++;
}

fwrite(STDOUT, "measured {$measured} files\n");
