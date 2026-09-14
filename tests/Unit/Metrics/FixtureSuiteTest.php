<?php

declare(strict_types=1);

/**
 * Fixture conventions for `tests/Fixtures/Metrics/<topic>/`:
 *
 * - `Fixture.php` holds valid PHP 8.4/8.5 source (except under `invalid/`,
 *   which is deliberately broken). It is namespaced under
 *   `Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\<Topic>` purely so every fixture's
 *   symbols are unique; it is never autoloaded, and is excluded from both
 *   Pint and PHPStan.
 * - `expected.php` returns `[symbol => [ccn2, lines, params]]`, where
 *   `symbol` is `Fully\Qualified\Class::method`. `ccn2` and `lines` are
 *   `int|null` (`null` means the metric does not apply); `params` is always
 *   an `int`.
 * - `invalid/expected.php` returns `['__error__' => true]` instead: the
 *   measurement engine must raise an analysis error for that fixture, never
 *   skip it silently.
 * - Anonymous class methods and property hooks are measured where they
 *   appear, but are never given their own `expected.php` entry, since they
 *   have no stable, addressable `Class::method` symbol.
 */

use Rdgs\PestCodeQuality\Analysis\Contracts\Measurer;

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
    $measurer = new class () implements Measurer {
        public function measure(string $path): array
        {
            throw new RuntimeException('measurement engine not implemented yet');
        }
    };

    /** @var array<string, array{ccn2: int|null, lines: int|null, params: int}>|array{__error__: true} $expected */
    $expected = require $expectedPath;

    expect($measurer->measure($fixturePath))->toBe($expected);
})->with('metric fixtures')->skip('measurement engine not implemented yet');
