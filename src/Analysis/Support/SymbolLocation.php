<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

/**
 * Where a measured symbol lives: its fully qualified name, the file that
 * declares it, and the line span of the declaration. Shared by
 * `MethodMeasurements` and `ClassMeasurements` so a new measured value on
 * either does not grow the constructor's own parameter count.
 */
final readonly class SymbolLocation
{
    public function __construct(
        public string $symbol,
        public string $path,
        public int $line,
        public int $endLine,
    ) {
    }
}
