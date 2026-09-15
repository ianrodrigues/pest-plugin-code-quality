<?php

declare(strict_types=1);

/**
 * `invalid/expected.php` returns `['__error__' => true]` instead of
 * measurements: the engine must raise an analysis error there, never skip
 * it silently. Anonymous class methods and property hooks are measured
 * but never appear in `expected.php`, having no stable `Class::method`
 * symbol.
 *
 * A key holding `::` is a method row, `[ccn2, lines, params]`. Every other
 * key is a class row, `['methods' => …, 'accessors' => …, 'properties' =>
 * …, 'inheritance' => …, 'classLines' => …]`, and a topic that declares
 * one declares one for every class-like it holds.
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
 * @param array<string, mixed> $expected
 * @return array<string, mixed>
 */
function fixture_rows_of(array $expected, bool $methods): array
{
    return array_filter(
        $expected,
        static fn (string $symbol): bool => str_contains($symbol, '::') === $methods,
        ARRAY_FILTER_USE_KEY,
    );
}

it('matches the measurer contract against every pinned fixture', function (string $fixturePath, string $expectedPath): void {
    $measurer = new AstMeasurer();

    /** @var array<string, mixed> $expected */
    $expected = require $expectedPath;

    if ($expected === ['__error__' => true]) {
        expect(fn (): array => $measurer->measure($fixturePath))->toThrow(AnalysisError::class);

        return;
    }

    expect($measurer->measure($fixturePath))->toBe(fixture_rows_of($expected, true));

    $classes = fixture_rows_of($expected, false);

    if ($classes !== []) {
        expect($measurer->measureClasses($fixturePath))->toBe($classes);
    }
})->with('metric fixtures');
