<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Baseline\BaselineMode;
use IanRodrigues\CodeQuality\Config;

beforeEach(function (): void {
    Config::reset();
});

afterEach(function (): void {
    Config::reset();
});

it('defaults to not strict', function (): void {
    expect(Config::isStrict())->toBeFalse();
});

it('turns strict mode on and off', function (): void {
    Config::strict();
    expect(Config::isStrict())->toBeTrue();

    Config::strict(false);
    expect(Config::isStrict())->toBeFalse();
});

it('defaults to no baseline, in checking mode', function (): void {
    expect(Config::baselinePath())->toBeNull()
        ->and(Config::baselineMode())->toBe(BaselineMode::Check);
});

it('remembers the configured baseline path', function (): void {
    Config::baseline('tests/quality-baseline.json');

    expect(Config::baselinePath())->toBe('tests/quality-baseline.json');
});

it('lets the command line override the configured baseline path', function (): void {
    Config::baseline('tests/quality-baseline.json');
    Config::overrideBaseline('build/other.json');

    expect(Config::baselinePath())->toBe('build/other.json');
});

it('keeps the override even when the configured path is set afterwards', function (): void {
    Config::overrideBaseline('build/other.json');
    Config::baseline('tests/quality-baseline.json');

    expect(Config::baselinePath())->toBe('build/other.json');
});

it('switches the baseline mode', function (): void {
    Config::useBaselineMode(BaselineMode::Generate);

    expect(Config::baselineMode())->toBe(BaselineMode::Generate);
});

it('resets to the defaults', function (): void {
    Config::strict();
    Config::baseline('tests/quality-baseline.json');
    Config::overrideBaseline('build/other.json');
    Config::useBaselineMode(BaselineMode::Tighten);

    Config::reset();

    expect(Config::isStrict())->toBeFalse()
        ->and(Config::baselinePath())->toBeNull()
        ->and(Config::baselineMode())->toBe(BaselineMode::Check);
});
