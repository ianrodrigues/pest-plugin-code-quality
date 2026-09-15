<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure\Wide;

it('passes when every class declares fewer methods than the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHaveMethodsAtMost(6));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('passes on a class that declares exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHaveMethodsAtMost(4));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending class, its declaration and the excess', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHaveMethodsAtMost(3));

    expect($message)->toBe(implode("\n", [
        Wide::class,
        'tests/Fixtures/App/Structure/Wide.php:7',
        '',
        'Class methods (methods v1): 4',
        'Allowed: at most 3',
        'Exceeded by: 1',
        'Counted:',
        '  __construct (line 13)',
        '  id (line 17)',
        '  setSize (line 22)',
        '  describe (line 29)',
        '',
        '1 class exceeds the limit',
    ]));
});

it('drops the accessors when asked to ignore them', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')
        ->classes()
        ->toHaveMethodsAtMost(2, ignoringAccessors: true));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('still counts what is left once the accessors are ignored', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')
        ->classes()
        ->toHaveMethodsAtMost(1, ignoringAccessors: true));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Class methods (methods v1): 2');
});

it('counts the methods an interface declares', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->interfaces()->toHaveMethodsAtMost(1));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Class methods (methods v1): 2');
});

it('counts the methods an enum declares', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->enums()->toHaveMethodsAtMost(0));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Class methods (methods v1): 1');
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHaveMethodsAtMost(1, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});
