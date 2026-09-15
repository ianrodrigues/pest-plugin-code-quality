<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Tests\Fixtures\App\Naming\ClassLength\Product;

it('passes on a class that measures exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Naming\ClassLength')->classes()->toHaveClassNamesAtMost(7));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending class, its declaration and the excess', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Naming\ClassLength')->classes()->toHaveClassNamesAtMost(6));

    expect($message)->toBe(implode("\n", [
        Product::class,
        'tests/Fixtures/App/Naming/ClassLength/Product.php:7',
        '',
        'Class name length (className v1): 7',
        'Allowed: at most 6',
        'Exceeded by: 1',
        '',
        '1 class exceeds the limit',
    ]));
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHaveClassNamesAtMost(1, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});
