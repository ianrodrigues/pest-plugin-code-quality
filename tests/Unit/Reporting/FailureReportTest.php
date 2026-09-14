<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Metrics\Metric;
use IanRodrigues\CodeQuality\Policies\Policy;
use IanRodrigues\CodeQuality\Policies\PolicyResult;
use IanRodrigues\CodeQuality\Policies\Violation;
use IanRodrigues\CodeQuality\Reporting\FailureReport;

function reporting_violation(string $symbol, string $path, int $line, int $value, int $limit): Violation
{
    return new Violation($symbol, $path, $line, Metric::Ccn2, $value, $limit);
}

it('formats a single violation followed by a singular summary line', function (): void {
    $result = new PolicyResult(
        Policy::complexity(10),
        [reporting_violation('App\Foo::bar', 'app/Foo.php', 42, 17, 10)],
        objectsSeen: 1,
        methodsMeasured: 1,
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Foo::bar',
        'app/Foo.php:42',
        '',
        'Method complexity (ccn2 v1): 17',
        'Allowed: at most 10',
        'Exceeded by: 7',
        '',
        '1 method exceeds the limit',
    ]));
});

it('separates several violations by one blank line and pluralizes the summary', function (): void {
    $result = new PolicyResult(
        Policy::complexity(10),
        [
            reporting_violation('App\Foo::bar', 'app/Foo.php', 10, 12, 10),
            reporting_violation('App\Foo::baz', 'app/Foo.php', 20, 15, 10),
        ],
        objectsSeen: 1,
        methodsMeasured: 2,
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Foo::bar',
        'app/Foo.php:10',
        '',
        'Method complexity (ccn2 v1): 12',
        'Allowed: at most 10',
        'Exceeded by: 2',
        '',
        'App\Foo::baz',
        'app/Foo.php:20',
        '',
        'Method complexity (ccn2 v1): 15',
        'Allowed: at most 10',
        'Exceeded by: 5',
        '',
        '2 methods exceed the limit',
    ]));
});

it('sorts violations by path then symbol regardless of discovery order', function (): void {
    $violations = [
        reporting_violation('App\Z::z', 'app/Z.php', 1, 12, 10),
        reporting_violation('App\A::b', 'app/A.php', 5, 12, 10),
        reporting_violation('App\A::a', 'app/A.php', 1, 12, 10),
    ];

    shuffle($violations);

    $result = new PolicyResult(Policy::complexity(10), $violations, objectsSeen: 3, methodsMeasured: 3);

    $report = FailureReport::for($result);

    $positionOf = static fn (string $needle): int => (int) mb_strpos($report, $needle);

    expect($report)->toStartWith("App\A::a\napp/A.php:1")
        ->and($positionOf('App\A::a'))->toBeLessThan($positionOf('App\A::b'))
        ->and($positionOf('App\A::b'))->toBeLessThan($positionOf('App\Z::z'));
});

it('truncates beyond the limit and points at the JSON report, regardless of discovery order', function (): void {
    $violations = [];

    for ($i = 1; $i <= 25; $i++) {
        $violations[] = reporting_violation(sprintf('App\Many::method%02d', $i), 'app/Many.php', $i, 2, 1);
    }

    shuffle($violations);

    $result = new PolicyResult(Policy::complexity(1), $violations, objectsSeen: 1, methodsMeasured: 25);

    $report = FailureReport::for($result);

    expect(substr_count($report, 'Allowed: at most 1'))->toBe(FailureReport::TRUNCATION_LIMIT)
        ->and($report)->toContain('App\Many::method01')
        ->and($report)->toContain('App\Many::method20')
        ->and($report)->not->toContain('App\Many::method21')
        ->and($report)->toEndWith(
            "25 methods exceed the limit\nShowing 20 of 25. The full list is available in the JSON report.",
        );
});

it('does not truncate exactly at the limit', function (): void {
    $violations = [];

    for ($i = 1; $i <= FailureReport::TRUNCATION_LIMIT; $i++) {
        $violations[] = reporting_violation(sprintf('App\Many::method%02d', $i), 'app/Many.php', $i, 2, 1);
    }

    $result = new PolicyResult(Policy::complexity(1), $violations, objectsSeen: 1, methodsMeasured: 20);

    $report = FailureReport::for($result);

    expect($report)
        ->toContain('App\Many::method20')
        ->toEndWith('20 methods exceed the limit')
        ->not->toContain('Showing');
});

it('states the accepted value and the increase when a baseline raised the ceiling', function (): void {
    $result = new PolicyResult(
        Policy::complexity(10),
        [new Violation('App\Foo::bar', 'app/Foo.php', 42, Metric::Ccn2, 17, 10, accepted: 16)],
        objectsSeen: 1,
        methodsMeasured: 1,
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Foo::bar',
        'app/Foo.php:42',
        '',
        'Method complexity (ccn2 v1): 17',
        'Allowed: at most 10',
        'Accepted: 16',
        'Increase: 1',
        'Exceeded by: 7',
        '',
        '1 method exceeds the limit',
    ]));
});
