<?php

declare(strict_types=1);

use Pest\Arch\Contracts\ArchExpectation;
use Pest\Expectation;

const MECHANISM_CHANGED = 'The upstream integration mechanism changed: Pest\Expectation::__call no longer '
    .'returns the result of an extended closure that declares a Pest\Arch\Contracts\ArchExpectation return '
    .'type. Every expectation in this package is built on that branch.';

it('returns an extension result declared as an arch expectation untouched', function (): void {
    $marker = new class () implements ArchExpectation {
        public function ignoring(array|string $targetsOrDependencies): self
        {
            return $this;
        }

        public function ignoringGlobalFunctions(): self
        {
            return $this;
        }

        public function mergeExcludeCallbacks(array $callbacks): void
        {
            //
        }

        public function excludeCallbacks(): array
        {
            return [];
        }
    };

    expect()->extend('qualityCanaryExpectation', fn (): ArchExpectation => $marker);

    expect(expect('anything')->__call('qualityCanaryExpectation', []))->toBe($marker, MECHANISM_CHANGED);
});

it('registers every expectation this package ships', function (string $name): void {
    expect(Expectation::hasExtend($name))->toBeTrue(MECHANISM_CHANGED);
})->with([
    'toHaveMethodComplexityAtMost',
    'toHaveMethodLinesAtMost',
    'toHaveMethodParametersAtMost',
]);

it('hands back an arch expectation from every expectation this package ships', function (string $name): void {
    $expectation = expect(FIXTURE_APP.'\Parsing')->classes()->{$name}(99);

    expect($expectation)->toBeInstanceOf(ArchExpectation::class, MECHANISM_CHANGED);
})->with([
    'toHaveMethodComplexityAtMost',
    'toHaveMethodLinesAtMost',
    'toHaveMethodParametersAtMost',
]);

it('leaves plain value expectations alone', function (): void {
    expect(1 + 1)->toBe(2);
});
