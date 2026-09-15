<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure\Wide;

it('passes on a class that measures exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHaveClassLinesAtMost(10));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending class, its declaration and the excess', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Structure')->classes()->toHaveClassLinesAtMost(9));

    expect($message)->toBe(implode("\n", [
        Wide::class,
        'tests/Fixtures/App/Structure/Wide.php:7',
        '',
        'Class lines (classLines v1): 10',
        'Allowed: at most 9',
        'Exceeded by: 1',
        '',
        '1 class exceeds the limit',
    ]));
});

it('counts the lines of an interface body', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->interfaces()->toHaveClassLinesAtMost(1));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Class lines (classLines v1): 2');
});

it('counts the lines of an enum body, cases included', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Structure')->enums()->toHaveClassLinesAtMost(3));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Class lines (classLines v1): 4');
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHaveClassLinesAtMost(1, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});
