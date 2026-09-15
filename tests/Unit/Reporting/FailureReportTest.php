<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Analysis\Contribution;
use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Analysis\Support\SymbolLocation;
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

it('names the longest variable identifier on a variableName failure', function (): void {
    $method = new MethodMeasurements(
        new SymbolLocation('App\Foo::bar', 'app/Foo.php', 42, 45),
        ccn2: 1,
        lines: 3,
        params: 2,
        methodName: 3,
        variableName: 28,
        longestVariableIdentifier: 'someVeryLongVariableName',
    );

    $result = new PolicyResult(
        Policy::variableNames(20),
        [new Violation('App\Foo::bar', 'app/Foo.php', 42, Metric::VariableName, 28, 20)],
        objectsSeen: 1,
        methodsMeasured: 1,
        measurements: [$method],
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Foo::bar',
        'app/Foo.php:42',
        '',
        'Variable name length (variableName v1): 28',
        'Longest: $someVeryLongVariableName (28)',
        'Allowed: at most 20',
        'Exceeded by: 8',
        '',
        '1 method exceeds the limit',
    ]));
});

it('groups ccn2 contributions by construct, most frequent first then earliest line', function (): void {
    $contributions = [
        new Contribution('if', 15),
        new Contribution('foreach', 14),
        new Contribution('??', 19),
        new Contribution('if', 22),
        new Contribution('&&', 22),
        new Contribution('&&', 22),
        new Contribution('??', 20),
        new Contribution('if', 27),
    ];

    $result = new PolicyResult(
        Policy::complexity(10),
        [new Violation('App\Foo::bar', 'app/Foo.php', 42, Metric::Ccn2, 11, 10, contributions: $contributions)],
        objectsSeen: 1,
        methodsMeasured: 1,
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Foo::bar',
        'app/Foo.php:42',
        '',
        'Method complexity (ccn2 v1): 11',
        'Allowed: at most 10',
        'Exceeded by: 1',
        'Counted:',
        '  3 x if (lines 15, 22, 27)',
        '  2 x ?? (lines 19, 20)',
        '  2 x && (lines 22, 22)',
        '  1 x foreach (line 14)',
        '',
        '1 method exceeds the limit',
    ]));
});

it('lists the first ten method contributions then points at the JSON report', function (): void {
    $contributions = array_map(
        static fn (int $i): Contribution => new Contribution(sprintf('method%02d', $i), $i * 10),
        range(1, 12),
    );

    $result = new PolicyResult(
        Policy::methods(10),
        [new Violation('App\Wide', 'app/Wide.php', 5, Metric::Methods, 12, 10, contributions: $contributions)],
        objectsSeen: 1,
        methodsMeasured: 0,
        classesMeasured: 1,
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Wide',
        'app/Wide.php:5',
        '',
        'Class methods (methods v1): 12',
        'Allowed: at most 10',
        'Exceeded by: 2',
        'Counted:',
        '  method01 (line 10)',
        '  method02 (line 20)',
        '  method03 (line 30)',
        '  method04 (line 40)',
        '  method05 (line 50)',
        '  method06 (line 60)',
        '  method07 (line 70)',
        '  method08 (line 80)',
        '  method09 (line 90)',
        '  method10 (line 100)',
        '  ... and 2 more (see the JSON report)',
        '',
        '1 class exceeds the limit',
    ]));
});

it('lists inheritance contributions without a line suffix', function (): void {
    $contributions = [
        new Contribution('App\Models\BaseModel', null),
        new Contribution('Illuminate\Database\Eloquent\Model', null),
    ];

    $result = new PolicyResult(
        Policy::inheritance(2),
        [new Violation('App\Models\Order', 'app/Models/Order.php', 8, Metric::Inheritance, 3, 2, contributions: $contributions)],
        objectsSeen: 1,
        methodsMeasured: 0,
        classesMeasured: 1,
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Models\Order',
        'app/Models/Order.php:8',
        '',
        'Inheritance depth (inheritance v1): 3',
        'Allowed: at most 2',
        'Exceeded by: 1',
        'Counted:',
        '  App\Models\BaseModel',
        '  Illuminate\Database\Eloquent\Model',
        '',
        '1 class exceeds the limit',
    ]));
});

it('prints no Counted line when a lines violation carries no contributions', function (): void {
    $result = new PolicyResult(
        Policy::lines(50),
        [new Violation('App\Foo::bar', 'app/Foo.php', 42, Metric::Lines, 60, 50)],
        objectsSeen: 1,
        methodsMeasured: 1,
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Foo::bar',
        'app/Foo.php:42',
        '',
        'Method lines (lines v1): 60',
        'Allowed: at most 50',
        'Exceeded by: 10',
        '',
        '1 method exceeds the limit',
    ]));
});

it('keeps the Longest line and prints no Counted line on a variableName failure', function (): void {
    $method = new MethodMeasurements(
        new SymbolLocation('App\Foo::baz', 'app/Foo.php', 12, 15),
        ccn2: 1,
        lines: 3,
        params: 1,
        methodName: 3,
        variableName: 30,
        longestVariableIdentifier: 'anotherRatherLongVariableName',
    );

    $result = new PolicyResult(
        Policy::variableNames(20),
        [new Violation('App\Foo::baz', 'app/Foo.php', 12, Metric::VariableName, 30, 20)],
        objectsSeen: 1,
        methodsMeasured: 1,
        measurements: [$method],
    );

    expect(FailureReport::for($result))->toBe(implode("\n", [
        'App\Foo::baz',
        'app/Foo.php:12',
        '',
        'Variable name length (variableName v1): 30',
        'Longest: $anotherRatherLongVariableName (30)',
        'Allowed: at most 20',
        'Exceeded by: 10',
        '',
        '1 method exceeds the limit',
    ]));
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
