<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Everything one file yields in a single traversal: its methods and its
 * class-like declarations. Iterating and counting an instance walks the
 * methods, which is what the method-scoped metrics measure.
 *
 * @implements IteratorAggregate<int, MethodMeasurements>
 */
final readonly class FileMeasurements implements Countable, IteratorAggregate
{
    /**
     * @param list<MethodMeasurements> $methods
     * @param list<ClassMeasurements> $classes
     */
    public function __construct(
        public string $path,
        private array $methods,
        private array $classes = [],
    ) {
    }

    public function count(): int
    {
        return count($this->methods);
    }

    /**
     * @return list<MethodMeasurements>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * @return list<ClassMeasurements>
     */
    public function classes(): array
    {
        return $this->classes;
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
