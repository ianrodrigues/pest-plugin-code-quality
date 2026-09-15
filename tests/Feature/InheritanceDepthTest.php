<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure\Descendant;

it('passes on a class that sits exactly at the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHaveInheritanceDepthAtMost(1));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending class, its declaration and the excess', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHaveInheritanceDepthAtMost(0));

    expect($message)->toBe(implode("\n", [
        Descendant::class,
        'tests/Fixtures/App/Structure/Descendant.php:7',
        '',
        'Inheritance depth (inheritance v1): 1',
        'Allowed: at most 0',
        'Exceeded by: 1',
        'Counted:',
        '  IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure\Ancestor',
        '',
        '1 class exceeds the limit',
    ]));
});

it('measures nothing on an interface, which extends no class', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->interfaces()->toHaveInheritanceDepthAtMost(0));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('measures nothing on an enum, which extends no class', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->enums()->toHaveInheritanceDepthAtMost(0));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHaveInheritanceDepthAtMost(1, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});
