<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Tests\Fixtures\App\Naming\MethodLength\Invoice;

it('passes on a method that measures exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Naming\MethodLength')->classes()->toHaveMethodNamesAtMost(19));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending method, its declaration and the excess, exempting magic methods', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Naming\MethodLength')->classes()->toHaveMethodNamesAtMost(5));

    expect($message)->toBe(implode("\n", [
        Invoice::class.'::calculateGrandTotal',
        'tests/Fixtures/App/Naming/MethodLength/Invoice.php:18',
        '',
        'Method name length (methodName v1): 19',
        'Allowed: at most 5',
        'Exceeded by: 14',
        '',
        '1 method exceeds the limit',
    ]));
});

it('does not count a magic method name against the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Naming\MethodLength\Magic')
        ->classes()
        ->toHaveMethodNamesAtMost(1));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHaveMethodNamesAtMost(1, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});
