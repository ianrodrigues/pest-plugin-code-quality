<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Tests\Fixtures\App\Naming\VariableLength\Order;

it('passes on a method that measures exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Naming\VariableLength')->classes()->toHaveVariableNamesAtMost(24));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending method, its declaration, the excess and the longest identifier', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Naming\VariableLength')->classes()->toHaveVariableNamesAtMost(23));

    expect($message)->toBe(implode("\n", [
        Order::class.'::total',
        'tests/Fixtures/App/Naming/VariableLength/Order.php:9',
        '',
        'Variable name length (variableName v1): 24',
        'Longest: $someVeryLongVariableName (24)',
        'Allowed: at most 23',
        'Exceeded by: 1',
        '',
        '1 method exceeds the limit',
    ]));
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHaveVariableNamesAtMost(1, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});
