<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Selection\SkippedFile;
use IanRodrigues\CodeQuality\Selection\SkippedFilesFound;
use IanRodrigues\CodeQuality\Selection\SkipReason;
use IanRodrigues\CodeQuality\Selection\TargetCoverage;

it('names the target, directories and counts, and suggests the opt-out', function (): void {
    $coverage = new TargetCoverage(
        'App\Empty',
        ['app/Empty'],
        3,
        0,
        0,
        0,
        [],
    );

    $message = EmptySelection::for('App\Empty', $coverage)->getMessage();

    expect($message)
        ->toContain('"App\Empty" matched no classes to measure.')
        ->toContain('Directories searched: app/Empty')
        ->toContain('PHP files found: 3')
        ->toContain('Objects loadable: 0')
        ->toContain('allowEmpty: true');
});

it('reports "(none resolved)" when no directory was found for the target', function (): void {
    $coverage = new TargetCoverage('App\Nowhere', [], 0, 0, 0, 0, []);

    expect(EmptySelection::for('App\Nowhere', $coverage)->getMessage())
        ->toContain('Directories searched: (none resolved)');
});

it('lists every skipped file with its reason', function (): void {
    $message = SkippedFilesFound::for([
        new SkippedFile('app/Foo.php', SkipReason::NotLoadable),
        new SkippedFile('app/Bar.php', SkipReason::Vendor),
    ])->getMessage();

    expect($message)
        ->toStartWith('2 file(s) were found but not analysed:')
        ->toContain('app/Foo.php (not loadable)')
        ->toContain('app/Bar.php (vendor)');
});
