<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Exceptions\QualityExpectationFailed;

it('selects an exact class', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Http\Controllers\OrderController')->toHaveMethodComplexityAtMost(1));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'Rdgs\PestCodeQuality\Tests\Fixtures\App\Http\Controllers\OrderController::cancel',
        'tests/Fixtures/App/Http/Controllers/OrderController.php:20',
        '',
        'Method complexity (ccn2 v1): 2',
        'Allowed: at most 1',
        'Exceeded by: 1',
        '',
        'Rdgs\PestCodeQuality\Tests\Fixtures\App\Http\Controllers\OrderController::show',
        'tests/Fixtures/App/Http/Controllers/OrderController.php:9',
        '',
        'Method complexity (ccn2 v1): 2',
        'Allowed: at most 1',
        'Exceeded by: 1',
    ]));
});

it('selects an array of targets', function (): void {
    $chain = policy(fn () => expect([FIXTURE_APP.'\Billing', FIXTURE_APP.'\BillingArchive'])
        ->classes()
        ->toHaveMethodComplexityAtMost(2));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'Rdgs\PestCodeQuality\Tests\Fixtures\App\BillingArchive\Old::reconcile',
        'tests/Fixtures/App/BillingArchive/Old.php:9',
        '',
        'Method complexity (ccn2 v1): 8',
        'Allowed: at most 2',
        'Exceeded by: 6',
    ]));
});

it('stops a namespace target at the segment boundary', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Billing')->classes()->toHaveMethodComplexityAtMost(2));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});

it('selects enums', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Support')->enums()->toHaveMethodComplexityAtMost(4));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'Rdgs\PestCodeQuality\Tests\Fixtures\App\Support\Status::label',
        'tests/Fixtures/App/Support/Status.php:13',
        '',
        'Method complexity (ccn2 v1): 5',
        'Allowed: at most 4',
        'Exceeded by: 1',
    ]));
});

it('selects traits', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Support')->traits()->toHaveMethodComplexityAtMost(2));

    expect($chain)->toThrow(QualityExpectationFailed::class, implode("\n", [
        'Rdgs\PestCodeQuality\Tests\Fixtures\App\Support\Concerns\Sluggable::slug',
        'tests/Fixtures/App/Support/Concerns/Sluggable.php:9',
        '',
        'Method complexity (ccn2 v1): 3',
        'Allowed: at most 2',
        'Exceeded by: 1',
    ]));
});

it('leaves out what the target filter excluded', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\Support')->classes()->toHaveMethodComplexityAtMost(1));

    expect($chain)->not->toThrow(QualityExpectationFailed::class);
});
