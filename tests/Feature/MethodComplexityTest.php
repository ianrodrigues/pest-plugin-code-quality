<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;

it('passes when every method is within the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodComplexityAtMost(20));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('passes on a method that measures exactly the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodComplexityAtMost(6));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('fails on a method one above the limit', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodComplexityAtMost(5));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Method complexity (ccn2 v1): 6');
});

it('reports the offending method, its location and the excess', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodComplexityAtMost(10)
        ->ignoring(FIXTURE_APP.'\Http\Controllers\Generated'));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers\CheckoutController::store',
        'tests/Fixtures/App/Http/Controllers/CheckoutController.php:14',
        '',
        'Method complexity (ccn2 v1): 21',
        'Allowed: at most 10',
        'Exceeded by: 11',
    ]));
});

it('reports every offending method in one failure, ordered by path', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')->classes()->toHaveMethodComplexityAtMost(10));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers\CheckoutController::store',
        'tests/Fixtures/App/Http/Controllers/CheckoutController.php:14',
        '',
        'Method complexity (ccn2 v1): 21',
        'Allowed: at most 10',
        'Exceeded by: 11',
        'Counted:',
        '  5 x ?? (lines 23, 24, 33, 34, 68)',
        '  4 x if (lines 29, 36, 41, 46)',
        '  3 x match arm (lines 48, 49, 50)',
        '  2 x case (lines 71, 74)',
        '  1 x foreach (line 28)',
        '  1 x && (line 36)',
        '  1 x || (line 41)',
        '  1 x while (line 57)',
        '  1 x catch (line 63)',
        '  1 x ? : (line 67)',
        '',
        'IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers\Generated\LegacyController::handle',
        'tests/Fixtures/App/Http/Controllers/Generated/LegacyController.php:9',
        '',
        'Method complexity (ccn2 v1): 15',
        'Allowed: at most 10',
        'Exceeded by: 5',
        'Counted:',
        '  4 x if (lines 23, 27, 34, 40)',
        '  3 x ?? (lines 16, 17, 18)',
        '  2 x match arm (lines 46, 47)',
        '  1 x || (line 27)',
        '  1 x foreach (line 31)',
        '  1 x && (line 40)',
        '  1 x for (line 51)',
        '  1 x do (line 55)',
    ]));
});
