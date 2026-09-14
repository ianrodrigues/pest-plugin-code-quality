<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Support;

use PhpParser\ErrorHandler;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * A `Parser` decorator that counts how many times `parse()` is invoked, so
 * tests can assert that unchanged content is only ever parsed once.
 */
final class CountingParser implements Parser
{
    public int $parseCount = 0;

    private readonly Parser $inner;

    public function __construct()
    {
        $this->inner = new ParserFactory()->createForNewestSupportedVersion();
    }

    public function parse(string $code, ?ErrorHandler $errorHandler = null): ?array
    {
        $this->parseCount++;

        return $this->inner->parse($code, $errorHandler);
    }

    public function getTokens(): array
    {
        return $this->inner->getTokens();
    }
}
