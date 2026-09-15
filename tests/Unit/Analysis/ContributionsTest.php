<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Analysis\AstMeasurer;
use IanRodrigues\CodeQuality\Analysis\ClassMeasurements;
use IanRodrigues\CodeQuality\Analysis\Contribution;
use IanRodrigues\CodeQuality\Analysis\FileMeasurements;
use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Metrics\Metric;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

/**
 * Parses and resolves names exactly as `AstMeasurer::measureFile()` does
 * internally, then feeds the result through the public `measureAst()`
 * path, since `measureFile()` itself is private.
 */
function measure_fixture(string $path): FileMeasurements
{
    $parser = new ParserFactory()->createForNewestSupportedVersion();
    $stmts = $parser->parse((string) file_get_contents($path)) ?? [];

    $traverser = new NodeTraverser();
    $traverser->addVisitor(new NameResolver());

    /** @var list<Stmt> $resolved */
    $resolved = $traverser->traverse($stmts);

    return new AstMeasurer()->measureAst($path, $resolved);
}

function method_named(FileMeasurements $file, string $symbol): MethodMeasurements
{
    return $file->bySymbol($symbol) ?? throw new RuntimeException("No such method: {$symbol}");
}

function class_named(FileMeasurements $file, string $symbol): ClassMeasurements
{
    foreach ($file->classes() as $class) {
        if ($class->symbol === $symbol) {
            return $class;
        }
    }

    throw new RuntimeException("No such class: {$symbol}");
}

/**
 * @param list<Contribution> $contributions
 * @return list<array{label: string, line: int|null}>
 */
function contribution_rows(array $contributions): array
{
    return array_map(static fn (Contribution $c): array => $c->toArray(), $contributions);
}

it('lists the && construct counted towards ccn2', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/boolean-operators/Fixture.php');
    $method = method_named($file, FIXTURE_METRICS.'\BooleanOperators\Fixture::andSymbol');

    expect(contribution_rows($method->contributionsTo(Metric::Ccn2)))->toBe([
        ['label' => '&&', 'line' => 12],
    ]);
});

it('tells the full ternary and the short ternary apart', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/ternary/Fixture.php');

    $full = method_named($file, FIXTURE_METRICS.'\Ternary\Fixture::fullTernary');
    $short = method_named($file, FIXTURE_METRICS.'\Ternary\Fixture::shortTernary');

    expect(contribution_rows($full->contributionsTo(Metric::Ccn2)))->toBe([
        ['label' => '? :', 'line' => 12],
    ])->and(contribution_rows($short->contributionsTo(Metric::Ccn2)))->toBe([
        ['label' => '?:', 'line' => 18],
    ]);
});

it('lists a match arm per condition and skips the default arm', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/match/Fixture.php');
    $method = method_named($file, FIXTURE_METRICS.'\Match_\Fixture::label');

    expect(contribution_rows($method->contributionsTo(Metric::Ccn2)))->toBe([
        ['label' => 'match arm', 'line' => 13],
        ['label' => 'match arm', 'line' => 14],
    ]);
});

it('lists one item per catch block', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/try-catch-finally/Fixture.php');
    $method = method_named($file, FIXTURE_METRICS.'\TryCatchFinally\Fixture::multiCatch');

    expect(contribution_rows($method->contributionsTo(Metric::Ccn2)))->toBe([
        ['label' => 'catch', 'line' => 29],
        ['label' => 'catch', 'line' => 31],
    ]);
});

it('lists every declared method, then only the non-accessors, then every property', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/class-methods/Fixture.php');
    $class = class_named($file, FIXTURE_METRICS.'\ClassMethods\Fixture');

    expect(contribution_rows($class->contributionsTo(Metric::Methods, false)))->toBe([
        ['label' => '__construct', 'line' => 35],
        ['label' => 'name', 'line' => 40],
        ['label' => 'setName', 'line' => 46],
        ['label' => 'setSize', 'line' => 54],
        ['label' => 'summary', 'line' => 60],
    ])
        ->and(contribution_rows($class->contributionsTo(Metric::Methods, true)))->toBe([
        ['label' => '__construct', 'line' => 35],
        ['label' => 'summary', 'line' => 60],
    ])
        ->and(contribution_rows($class->contributionsTo(Metric::Properties, false)))->toBe([
        ['label' => 'touched', 'line' => 30],
        ['label' => 'touchedAt', 'line' => 32],
        ['label' => 'name', 'line' => 35],
        ['label' => 'size', 'line' => 35],
    ]);
});

it('lists the parents of the deepest class nearest first', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/inheritance-depth/Fixture.php');
    $class = class_named($file, FIXTURE_METRICS.'\InheritanceDepth\Fixture');

    expect(contribution_rows($class->contributionsTo(Metric::Inheritance, false)))->toBe([
        ['label' => FIXTURE_METRICS.'\InheritanceDepth\Leaf', 'line' => null],
        ['label' => FIXTURE_METRICS.'\InheritanceDepth\Middle', 'line' => null],
        ['label' => FIXTURE_METRICS.'\InheritanceDepth\Root', 'line' => null],
    ]);
});

it('returns no contribution for a bodiless method', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/inheritance-depth/Fixture.php');
    $method = method_named($file, FIXTURE_METRICS.'\InheritanceDepth\Shape::area');

    expect($method->contributionsTo(Metric::Ccn2))->toBeEmpty();
});

it('returns no contribution for a method metric with no breakdown', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/boolean-operators/Fixture.php');
    $method = method_named($file, FIXTURE_METRICS.'\BooleanOperators\Fixture::andSymbol');

    expect($method->contributionsTo(Metric::Lines))->toBeEmpty()
        ->and($method->contributionsTo(Metric::Params))->toBeEmpty()
        ->and($method->contributionsTo(Metric::MethodName))->toBeEmpty()
        ->and($method->contributionsTo(Metric::VariableName))->toBeEmpty()
        ->and($method->contributionsTo(Metric::Methods))->toBeEmpty()
        ->and($method->contributionsTo(Metric::Properties))->toBeEmpty()
        ->and($method->contributionsTo(Metric::Inheritance))->toBeEmpty()
        ->and($method->contributionsTo(Metric::ClassLines))->toBeEmpty()
        ->and($method->contributionsTo(Metric::ClassName))->toBeEmpty();
});

it('returns no contribution for a class metric with no breakdown', function (): void {
    $file = measure_fixture(__DIR__.'/../../Fixtures/Metrics/class-methods/Fixture.php');
    $class = class_named($file, FIXTURE_METRICS.'\ClassMethods\Fixture');

    expect($class->contributionsTo(Metric::Ccn2, false))->toBeEmpty()
        ->and($class->contributionsTo(Metric::Lines, false))->toBeEmpty()
        ->and($class->contributionsTo(Metric::Params, false))->toBeEmpty()
        ->and($class->contributionsTo(Metric::ClassLines, false))->toBeEmpty()
        ->and($class->contributionsTo(Metric::ClassName, false))->toBeEmpty()
        ->and($class->contributionsTo(Metric::MethodName, false))->toBeEmpty()
        ->and($class->contributionsTo(Metric::VariableName, false))->toBeEmpty();
});
