<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Reporting\FailureReport;

it('reports one violation with an exact message and singular summary', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodComplexityAtMost(5));

    expect($message)->toBe(implode("\n", [
        'IanRodrigues\CodeQuality\Tests\Fixtures\App\Parsing\Parser::parse',
        'tests/Fixtures/App/Parsing/Parser.php:9',
        '',
        'Method complexity (ccn2 v1): 6',
        'Allowed: at most 5',
        'Exceeded by: 1',
        '',
        '1 method exceeds the limit',
    ]));
});

it('reports several violations with an exact message, sorted, and a plural summary', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodComplexityAtMost(10));

    expect($message)->toBe(implode("\n", [
        'IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers\CheckoutController::store',
        'tests/Fixtures/App/Http/Controllers/CheckoutController.php:14',
        '',
        'Method complexity (ccn2 v1): 21',
        'Allowed: at most 10',
        'Exceeded by: 11',
        '',
        'IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers\Generated\LegacyController::handle',
        'tests/Fixtures/App/Http/Controllers/Generated/LegacyController.php:9',
        '',
        'Method complexity (ccn2 v1): 15',
        'Allowed: at most 10',
        'Exceeded by: 5',
        '',
        '2 methods exceed the limit',
    ]));
});

it('truncates a violation set larger than the limit and points at the JSON report', function (): void {
    $message = policy_failure(fn () => expect(FIXTURE_APP.'\Reporting')
        ->classes()
        ->toHaveMethodComplexityAtMost(1));

    $blocks = [];

    for ($i = 1; $i <= FailureReport::TRUNCATION_LIMIT; $i++) {
        $number = sprintf('%02d', $i);
        $line = 9 + ($i - 1) * 7;

        $blocks[] = implode("\n", [
            "IanRodrigues\CodeQuality\Tests\Fixtures\App\Reporting\ManyViolations::method{$number}",
            "tests/Fixtures/App/Reporting/ManyViolations.php:{$line}",
            '',
            'Method complexity (ccn2 v1): 2',
            'Allowed: at most 1',
            'Exceeded by: 1',
        ]);
    }

    expect($message)->toBe(
        implode("\n\n", $blocks)
        ."\n\n25 methods exceed the limit"
        ."\nShowing 20 of 25. The full list is available in the JSON report.",
    );
});

it('points the collision editor at the first listed violation, not discovery order', function (): void {
    try {
        policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')->classes()->toHaveMethodComplexityAtMost(10))();

        $this->fail('Expected the policy to fail.');
    } catch (QualityExpectationFailed $failure) {
        $frame = $failure->toCollisionEditor();
    }

    expect($frame->getFile())->toBe('tests/Fixtures/App/Http/Controllers/CheckoutController.php')
        ->and($frame->getLine())->toBe(14);
});
