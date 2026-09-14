<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Exceptions\QualityExpectationFailed;

it('passes when every method body is within the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodLinesAtMost(40));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('passes on a method body that measures exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodLinesAtMost(8));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('reports the offending method, its location and the excess', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodLinesAtMost(40)
        ->ignoring(FIXTURE_APP.'\Http\Controllers\Generated'));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'Rdgs\PestCodeQuality\Tests\Fixtures\App\Http\Controllers\CheckoutController::store',
        'tests/Fixtures/App/Http/Controllers/CheckoutController.php:14',
        '',
        'Method lines (lines v1): 45',
        'Allowed: at most 40',
        'Exceeded by: 5',
    ]));
});
