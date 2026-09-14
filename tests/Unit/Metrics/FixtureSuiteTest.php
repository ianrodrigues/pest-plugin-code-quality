<?php

declare(strict_types=1);

/**
 * `invalid/expected.php` returns `['__error__' => true]` instead of
 * measurements: the engine must raise an analysis error there, never skip
 * it silently. Anonymous class methods and property hooks are measured
 * but never appear in `expected.php`, having no stable `Class::method`
 * symbol.
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

it('matches the measurer contract against every pinned fixture', function (string $fixturePath, string $expectedPath): void {
    $measurer = new AstMeasurer();

    /** @var array<string, array{int|null, int|null, int}>|array{__error__: true} $expected */
    $expected = require $expectedPath;

    if ($expected === ['__error__' => true]) {
        expect(fn (): array => $measurer->measure($fixturePath))->toThrow(AnalysisError::class);

        return;
    }

    expect($measurer->measure($fixturePath))->toBe($expected);
})->with('metric fixtures');
