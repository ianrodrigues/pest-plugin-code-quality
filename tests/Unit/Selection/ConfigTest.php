<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Selection\Config;

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

it('resets to the default', function (): void {
    Config::strict();

    Config::reset();

    expect(Config::isStrict())->toBeFalse();
});
