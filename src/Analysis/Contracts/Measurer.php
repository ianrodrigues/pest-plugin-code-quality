<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Analysis\Contracts;

/**
 * Measures the metrics defined in the "Metric definitions" README section
 * for every eligible symbol declared in a PHP source file.
 */
interface Measurer
{
    /**
     * Measures every method declared in the file at `$path`.
     *
     * The result maps each `Fully\Qualified\Class::method` symbol to its
     * `[ccn2, lines, params]` metric values, in that order. A `null` value
     * means the metric does not apply to that symbol (for example, `ccn2`
     * and `lines` on an abstract or interface method); `params` is always
     * an `int`.
     *
     * @return array<string, array{int|null, int|null, int}>
     */
    public function measure(string $path): array;
}
