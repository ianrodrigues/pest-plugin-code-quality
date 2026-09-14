<?php

declare(strict_types=1);

it('loads the package autoload file and exposes a version marker', function (): void {
    expect(function_exists('Pest\Quality\version'))->toBeTrue();
    expect(\Pest\Quality\version())->toMatch('/^\d+\.\d+\.\d+$/');
});
