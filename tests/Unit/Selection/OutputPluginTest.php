<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Selection\Plugins\OutputPlugin;
use Rdgs\PestCodeQuality\Selection\SkippedFile;
use Rdgs\PestCodeQuality\Selection\SkipReason;
use Rdgs\PestCodeQuality\Selection\WarningsCollector;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(function (): void {
    WarningsCollector::reset();
});

afterEach(function (): void {
    WarningsCollector::reset();
});

it('prints nothing when nothing was skipped', function (): void {
    $output = new BufferedOutput();

    (new OutputPlugin($output))->addOutput(0);

    expect($output->fetch())->toBe('');
});

it('returns the exit code untouched', function (): void {
    $output = new BufferedOutput();

    expect((new OutputPlugin($output))->addOutput(3))->toBe(3);
});

it('prints the summary line and one line per skipped file', function (): void {
    WarningsCollector::record([
        new SkippedFile('app/Foo.php', SkipReason::NotLoadable),
        new SkippedFile('app/Bar.php', SkipReason::NamespaceMismatch),
    ]);

    $output = new BufferedOutput();

    (new OutputPlugin($output))->addOutput(0);

    $printed = $output->fetch();

    expect($printed)
        ->toContain('Quality: 2 files were found but not analysed (run with --quality-inspect for details)')
        ->toContain('app/Bar.php (namespace mismatch)')
        ->toContain('app/Foo.php (not loadable)');
});

it('caps the printed files at ten', function (): void {
    foreach (range(1, 15) as $index) {
        WarningsCollector::record([new SkippedFile(sprintf('app/File%02d.php', $index), SkipReason::NoAst)]);
    }

    $output = new BufferedOutput();

    (new OutputPlugin($output))->addOutput(0);

    $lines = array_values(array_filter(explode("\n", $output->fetch())));
    $fileLines = array_filter($lines, static fn (string $line): bool => str_contains($line, 'File'));

    expect($fileLines)->toHaveCount(10);
});
