<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Contracts;

/**
 * Measures the metrics defined in the "Metric definitions" README section
 * for every eligible symbol declared in a PHP source file.
 */
interface Measurer
{
    /**
     * Maps each `Fully\Qualified\Class::method` symbol to its
     * `[ccn2, lines, params]` values; `null` means the metric does not
     * apply, as `ccn2`/`lines` do not on an abstract or interface method.
     *
     * @return array<string, array{int|null, int|null, int}>
     */
    public function measure(string $path): array;

    /**
     * Maps each `Fully\Qualified\Class` symbol to its class-level values.
     * `inheritance` is `null` only for an interface, a trait or an enum; a
     * class with no parent measures `0`, not `null`.
     *
     * @return array<string, array{methods: int, accessors: int, properties: int, inheritance: int|null, classLines: int}>
     */
    public function measureClasses(string $path): array;
}
