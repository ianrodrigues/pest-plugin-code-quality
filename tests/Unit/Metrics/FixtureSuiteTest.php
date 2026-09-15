<?php

declare(strict_types=1);

/**
 * `invalid/expected.php` returns `['__error__' => true]` to signal an analysis error; anonymous
 * class methods and property hooks are measured but never appear here (no stable symbol).
 * Method rows are `[ccn2, lines, params]` or `[ccn2, lines, params, methodName, variableName]`;
 * class rows are keyed by class name; only the values a row gives are compared.
 */

use IanRodrigues\CodeQuality\Analysis\AnalysisError;
use IanRodrigues\CodeQuality\Analysis\AstMeasurer;

dataset('metric fixtures', function (): iterable {
    $root = __DIR__ . '/../../Fixtures/Metrics';

    /** @var list<string> $topicDirs */
    $topicDirs = glob($root . '/*', GLOB_ONLYDIR) ?: [];

    foreach ($topicDirs as $topicDir) {
        $fixture = $topicDir . '/Fixture.php';
        $expected = $topicDir . '/expected.php';

        if (! is_file($fixture) || ! is_file($expected)) {
            continue;
        }

        yield basename($topicDir) => [$fixture, $expected];
    }
});

/**
 * Every row `expected.php` declares is a list or a map of `int|null`
 * values; this both selects method or class rows by their key shape and
 * gives each value the type the rest of this file compares against.
 *
 * @param array<string, mixed> $expected
 * @return array<string, array<int|string, int|null>>
 */
function fixture_rows_of(array $expected, bool $methods): array
{
    $rows = [];

    foreach ($expected as $symbol => $row) {
        if (str_contains($symbol, '::') !== $methods || ! is_array($row)) {
            continue;
        }

        $rows[$symbol] = array_map(
            static fn (mixed $value): int|null => is_int($value) ? $value : null,
            $row,
        );
    }

    return $rows;
}

/**
 * Trims each measured method row down to as many values as the fixture's
 * own row gives, so a fixture written before `methodName` and
 * `variableName` existed keeps comparing only `[ccn2, lines, params]`.
 *
 * @param array<string, array<int|string, int|null>> $actual
 * @param array<string, array<int|string, int|null>> $expected
 * @return array<string, array<int|string, int|null>>
 */
function fixture_method_rows(array $actual, array $expected): array
{
    $trimmed = [];

    foreach ($actual as $symbol => $row) {
        $length = array_key_exists($symbol, $expected) ? count($expected[$symbol]) : count($row);
        $trimmed[$symbol] = array_slice($row, 0, $length);
    }

    return $trimmed;
}

/**
 * Drops any measured class key the fixture's own row does not give, so a
 * fixture written before `className` existed keeps comparing only its
 * original keys.
 *
 * @param array<string, array<int|string, int|null>> $actual
 * @param array<string, array<int|string, int|null>> $expected
 * @return array<string, array<int|string, int|null>>
 */
function fixture_class_rows(array $actual, array $expected): array
{
    $trimmed = [];

    foreach ($actual as $symbol => $row) {
        $trimmed[$symbol] = array_key_exists($symbol, $expected)
            ? array_intersect_key($row, $expected[$symbol])
            : $row;
    }

    return $trimmed;
}

it('matches the measurer contract against every pinned fixture', function (string $fixturePath, string $expectedPath): void {
    $measurer = new AstMeasurer();

    /** @var array<string, mixed> $expected */
    $expected = require $expectedPath;

    if ($expected === ['__error__' => true]) {
        expect(fn (): array => $measurer->measure($fixturePath))->toThrow(AnalysisError::class);

        return;
    }

    $expectedMethods = fixture_rows_of($expected, true);

    expect(fixture_method_rows($measurer->measure($fixturePath), $expectedMethods))->toBe($expectedMethods);

    $expectedClasses = fixture_rows_of($expected, false);

    if ($expectedClasses !== []) {
        expect(fixture_class_rows($measurer->measureClasses($fixturePath), $expectedClasses))->toBe($expectedClasses);
    }
})->with('metric fixtures');
