<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Exceptions\QualityExpectationFailed;

it('composes after a built-in expectation', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')
        ->classes()
        ->toBeFinal()
        ->toHaveMethodComplexityAtMost(20));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('composes before a built-in expectation', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')
        ->classes()
        ->toHaveMethodComplexityAtMost(20)
        ->toBeFinal());

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('still fails after a built-in expectation passed', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')
        ->classes()
        ->toBeFinal()
        ->toHaveMethodComplexityAtMost(1));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Parser::parse');
});

it('still fails before a built-in expectation runs', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')
        ->classes()
        ->toHaveMethodComplexityAtMost(1)
        ->toBeFinal());

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Parser::parse');
});

it('composes with the other method limits on one target', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')
        ->classes()
        ->toHaveMethodComplexityAtMost(20)
        ->toHaveMethodLinesAtMost(40)
        ->toHaveMethodParametersAtMost(4));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

arch('reads naturally as an architecture test')
    ->expect(FIXTURE_APP.'\Http\Controllers')
    ->classes()
    ->toHaveMethodComplexityAtMost(25)
    ->toHaveMethodLinesAtMost(50)
    ->toHaveMethodParametersAtMost(8);
