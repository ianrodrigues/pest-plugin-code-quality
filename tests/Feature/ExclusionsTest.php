<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Exceptions\QualityExpectationFailed;

it('keeps failing on the offenders that ignoring did not cover', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodComplexityAtMost(10)
        ->ignoring(FIXTURE_APP.'\Http\Controllers\Generated'));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'CheckoutController::store');
});

it('never reports a method from an ignored sub-namespace', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodComplexityAtMost(10)
        ->ignoring(FIXTURE_APP.'\Http\Controllers\Generated'));

    expect($message)->not->toContain('LegacyController');
});

it('passes once every offender is ignored', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodComplexityAtMost(10)
        ->ignoring([
            FIXTURE_APP.'\Http\Controllers\Generated',
            FIXTURE_APP.'\Http\Controllers\CheckoutController',
        ]));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('honours the ignore list declared on the test itself', function (): void {
    $this->arch()->ignore([
        FIXTURE_APP.'\Http\Controllers\Generated',
        FIXTURE_APP.'\Http\Controllers\CheckoutController',
    ]);

    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')->classes()->toHaveMethodComplexityAtMost(10));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('honours an inline ignore on the method declaration', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Legacy')->classes()->toHaveMethodComplexityAtMost(1));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});
