<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Exceptions\InvalidLimit;
use Rdgs\PestCodeQuality\Exceptions\QualityExpectationFailed;
use Rdgs\PestCodeQuality\Exceptions\UnsupportedModifier;

it('rejects negation with an explanation instead of the raw failure', function (): void {
    expect(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->not->toHaveMethodComplexityAtMost(1))
        ->toThrow(UnsupportedModifier::class, UnsupportedModifier::NOT);
});

it('rejects negation reached straight from the expectation', function (): void {
    expect(fn () => expect(FIXTURE_APP.'\Parsing')->not->toHaveMethodLinesAtMost(1))
        ->toThrow(UnsupportedModifier::class, UnsupportedModifier::NOT);
});

it('rejects negation for every method limit', function (string $method): void {
    expect(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->not->{$method}(1))
        ->toThrow(UnsupportedModifier::class, UnsupportedModifier::NOT);
})->with([
    'toHaveMethodComplexityAtMost',
    'toHaveMethodLinesAtMost',
    'toHaveMethodParametersAtMost',
]);

it('rejects a negative limit when the expectation is declared', function (string $method, string $description): void {
    expect(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->{$method}(-1))
        ->toThrow(InvalidLimit::class, "{$description} limits must be zero or greater, but -1 was given.");
})->with([
    ['toHaveMethodComplexityAtMost', 'Method complexity'],
    ['toHaveMethodLinesAtMost', 'Method lines'],
    ['toHaveMethodParametersAtMost', 'Method parameters'],
]);

it('accepts a zero limit and measures against it', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodComplexityAtMost(0));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Allowed: at most 0');
});
