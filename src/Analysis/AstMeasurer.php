<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use IanRodrigues\CodeQuality\Analysis\Contracts\Measurer;
use IanRodrigues\CodeQuality\Analysis\Support\MetricsVisitor;
use IanRodrigues\CodeQuality\Analysis\Support\SourceTokens;
use PhpParser\Error as ParserError;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * Results are cached per real path and content hash, so measuring the same
 * unchanged file twice only parses it once.
 */
final readonly class AstMeasurer implements Measurer
{
    private Parser $parser;

    private MeasurementCache $cache;

    public function __construct(?Parser $parser = null, ?MeasurementCache $cache = null)
    {
        $this->parser = $parser ?? new ParserFactory()->createForNewestSupportedVersion();
        $this->cache = $cache ?? new MeasurementCache();
    }

    public function measure(string $path): array
    {
        $result = [];

        foreach ($this->measureFile($path) as $method) {
            if ($method->isAnonymous()) {
                continue;
            }

            $result[$method->symbol] = $method->toArray();
        }

        return $result;
    }

    /**
     * Measures a file whose AST has already been parsed and had
     * `NameResolver` run over it, skipping both steps here.
     *
     * @param list<Stmt> $stmts
     */
    public function measureAst(string $path, array $stmts): FileMeasurements
    {
        $contents = $this->read($path);
        $tokens = new SourceTokens($contents);
        $visitor = new MetricsVisitor($path, $tokens);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        return new FileMeasurements($path, $visitor->methods());
    }

    private function measureFile(string $path): FileMeasurements
    {
        $contents = $this->read($path);

        return $this->cache->remember($path, $contents, function () use ($path, $contents): FileMeasurements {
            $stmts = $this->parse($path, $contents);

            return $this->measureAst($path, $stmts);
        });
    }

    /**
     * @return list<Stmt>
     */
    private function parse(string $path, string $contents): array
    {
        try {
            $stmts = $this->parser->parse($contents);
        } catch (ParserError $error) {
            throw AnalysisError::unparsable($path, $error->getMessage(), $error);
        }

        if ($stmts === null) {
            throw AnalysisError::unparsable($path, 'the parser returned no statements.');
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        /** @var list<Stmt> */
        return $traverser->traverse($stmts);
    }

    private function read(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw AnalysisError::unreadable($path);
        }

        return $contents;
    }
}
