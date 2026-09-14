<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Selection\SkippedFile;
use Rdgs\PestCodeQuality\Selection\SkipReason;
use Rdgs\PestCodeQuality\Selection\WarningsCollector;

beforeEach(function (): void {
    WarningsCollector::reset();
});

afterEach(function (): void {
    WarningsCollector::reset();
});

it('starts empty', function (): void {
    expect(WarningsCollector::all())->toBe([]);
});

it('records files sorted by path', function (): void {
    WarningsCollector::record([
        new SkippedFile('b.php', SkipReason::NotLoadable),
        new SkippedFile('a.php', SkipReason::NoAst),
    ]);

    $files = WarningsCollector::all();

    expect($files)->toHaveCount(2)
        ->and($files[0]->path)->toBe('a.php')
        ->and($files[1]->path)->toBe('b.php');
});

it('keeps only the first reason seen for a path', function (): void {
    WarningsCollector::record([new SkippedFile('a.php', SkipReason::NotLoadable)]);
    WarningsCollector::record([new SkippedFile('a.php', SkipReason::Vendor)]);

    $files = WarningsCollector::all();

    expect($files)->toHaveCount(1)
        ->and($files[0]->reason)->toBe(SkipReason::NotLoadable);
});

it('clears on reset', function (): void {
    WarningsCollector::record([new SkippedFile('a.php', SkipReason::NotLoadable)]);

    WarningsCollector::reset();

    expect(WarningsCollector::all())->toBe([]);
});
