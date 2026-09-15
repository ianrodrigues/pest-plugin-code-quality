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
        'Counted:',
        '  2 x if (lines 14, 18)',
        '  1 x foreach (line 13)',
        '  1 x || (line 18)',
        '  1 x ? : (line 22)',
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
        $ifLine = $line + 2;

        $blocks[] = implode("\n", [
            "IanRodrigues\CodeQuality\Tests\Fixtures\App\Reporting\ManyViolations::method{$number}",
            "tests/Fixtures/App/Reporting/ManyViolations.php:{$line}",
            '',
            'Method complexity (ccn2 v1): 2',
            'Allowed: at most 1',
            'Exceeded by: 1',
            'Counted:',
            "  1 x if (line {$ifLine})",
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
