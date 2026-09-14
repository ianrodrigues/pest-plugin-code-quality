<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Exceptions\QualityExpectationFailed;

it('passes an ordinary assertion unrelated to any policy', function (): void {
    expect(1 + 1)->toBe(2);
});

it('keeps its own result when a policy declared in the same file fails', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodComplexityAtMost(5));

    expect($chain)->toThrow(QualityExpectationFailed::class, 'Method complexity (ccn2 v1): 6');
});

it('passes another ordinary assertion declared after the failing policy', function (): void {
    expect(2 + 2)->toBe(4);
});
