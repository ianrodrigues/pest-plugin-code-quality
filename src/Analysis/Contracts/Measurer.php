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
     * `[ccn2, lines, params]` values. A `null` value means the metric does
     * not apply to that symbol (for example, `ccn2` and `lines` on an
     * abstract or interface method); `params` is always an `int`.
     *
     * @return array<string, array{int|null, int|null, int}>
     */
    public function measure(string $path): array;
}
