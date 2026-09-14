<?php

declare(strict_types=1);

it('loads the package autoload file and exposes a version marker', function (): void {
    expect(function_exists('Rdgs\PestCodeQuality\version'))->toBeTrue();
    expect(\Rdgs\PestCodeQuality\version())->toMatch('/^\d+\.\d+\.\d+$/');
});
