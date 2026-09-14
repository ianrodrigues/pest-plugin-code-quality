<?php

declare(strict_types=1);

use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;
use Rdgs\PestCodeQuality\Analysis\AstMeasurer;
use Rdgs\PestCodeQuality\Analysis\FileMeasurements;
use Rdgs\PestCodeQuality\Analysis\MeasurementCache;
use Rdgs\PestCodeQuality\Analysis\MethodMeasurements;
use Rdgs\PestCodeQuality\Tests\Support\CountingParser;

it('only computes a fresh value once for the same path and content', function (): void {
    $cache = new MeasurementCache();
    $computations = 0;
    $result = new FileMeasurements('irrelevant.php', []);

    $compute = function () use (&$computations, $result): FileMeasurements {
        $computations++;

        return $result;
    };

    $first = $cache->remember('/some/path.php', 'contents-a', $compute);
    $second = $cache->remember('/some/path.php', 'contents-a', $compute);

    expect($computations)->toBe(1);
    expect($first)->toBe($result);
    expect($second)->toBe($result);
});

it('recomputes when the content for the same path changes', function (): void {
    $cache = new MeasurementCache();
    $computations = 0;

    $compute = function () use (&$computations): FileMeasurements {
        $computations++;

        return new FileMeasurements('irrelevant.php', []);
    };

    $cache->remember('/some/path.php', 'contents-a', $compute);
    $cache->remember('/some/path.php', 'contents-b', $compute);

    expect($computations)->toBe(2);
});

it('parses an unchanged file once across repeated measurements', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pest-quality-cache-') . '.php';
    file_put_contents($path, "<?php\n\nfinal class Cached\n{\n    public function hello(): string\n    {\n        return 'hi';\n    }\n}\n");

    $parser = new CountingParser();
    $measurer = new AstMeasurer($parser, new MeasurementCache());

    try {
        $first = $measurer->measure($path);
        $second = $measurer->measure($path);

        expect($parser->parseCount)->toBe(1);
        expect($second)->toBe($first);

        file_put_contents($path, "<?php\n\nfinal class Cached\n{\n    public function hello(): string\n    {\n        return 'bye';\n    }\n}\n");

        $measurer->measure($path);

        expect($parser->parseCount)->toBe(2);
    } finally {
        unlink($path);
    }
});

it('gives measureAst the same result as measure for the same file', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pest-quality-equivalence-') . '.php';
    file_put_contents($path, <<<'PHP'
        <?php

        namespace Equivalence;

        final class Fixture
        {
            public function greet(bool $formal): string
            {
                if ($formal) {
                    return 'Good day.';
                }

                return 'hi';
            }
        }
        PHP);

    try {
        $measurer = new AstMeasurer();

        $viaMeasure = $measurer->measure($path);

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $stmts = $parser->parse((string) file_get_contents($path)) ?? [];
        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver());

        /** @var list<Stmt> $resolved */
        $resolved = $traverser->traverse($stmts);
        $fileMeasurements = $measurer->measureAst($path, $resolved);

        $viaMeasureAst = [];

        foreach ($fileMeasurements as $method) {
            if ($method->isAnonymous()) {
                continue;
            }

            $viaMeasureAst[$method->symbol] = $method->toArray();
        }

        expect($viaMeasureAst)->toBe($viaMeasure);
    } finally {
        unlink($path);
    }
});

it('exposes methods by symbol and counts them on FileMeasurements', function (): void {
    $measurements = new FileMeasurements('irrelevant.php', [
        new MethodMeasurements('A::a', 'irrelevant.php', 1, 3, 1, 1, 0),
        new MethodMeasurements('A::b', 'irrelevant.php', 5, 7, 1, 1, 0),
    ]);

    expect($measurements)->toHaveCount(2);
    expect($measurements->bySymbol('A::a')?->line)->toBe(1);
    expect($measurements->bySymbol('A::missing'))->toBeNull();
    expect(iterator_to_array($measurements))->toHaveCount(2);
});
