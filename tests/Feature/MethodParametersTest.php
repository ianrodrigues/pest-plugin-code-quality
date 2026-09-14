<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;

it('passes when every method is within the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodParametersAtMost(4));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('passes on a method that declares exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\BillingArchive')->classes()->toHaveMethodParametersAtMost(3));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending method, its location and the excess', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodParametersAtMost(4)
        ->ignoring(FIXTURE_APP.'\Http\Controllers\Generated'));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers\CheckoutController::store',
        'tests/Fixtures/App/Http/Controllers/CheckoutController.php:14',
        '',
        'Method parameters (params v1): 7',
        'Allowed: at most 4',
        'Exceeded by: 3',
    ]));
});
