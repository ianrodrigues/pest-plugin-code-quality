<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure\Wide;

it('passes on a class that declares exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHavePropertiesAtMost(3));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending class, its declaration and the excess', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHavePropertiesAtMost(2));

    expect($message)->toBe(implode("\n", [
        Wide::class,
        'tests/Fixtures/App/Structure/Wide.php:7',
        '',
        'Class properties (properties v1): 3',
        'Allowed: at most 2',
        'Exceeded by: 1',
        '',
        '1 class exceeds the limit',
    ]));
});

it('counts no property on an interface', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->interfaces()->toHavePropertiesAtMost(0));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('counts no property on an enum', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->enums()->toHavePropertiesAtMost(0));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHavePropertiesAtMost(1, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});
