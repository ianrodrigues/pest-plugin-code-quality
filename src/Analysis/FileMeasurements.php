<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, MethodMeasurements>
 */
final readonly class FileMeasurements implements Countable, IteratorAggregate
{
    /**
     * @param list<MethodMeasurements> $methods
     */
    public function __construct(
        public string $path,
        private array $methods,
    ) {
    }

    public function count(): int
    {
        return count($this->methods);
    }

    public function bySymbol(string $symbol): ?MethodMeasurements
    {
        foreach ($this->methods as $method) {
            if ($method->symbol === $symbol) {
                return $method;
            }
        }

        return null;
    }

    /**
     * @return Traversable<int, MethodMeasurements>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->methods);
    }
}
