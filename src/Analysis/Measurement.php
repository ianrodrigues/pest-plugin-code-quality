<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use IanRodrigues\CodeQuality\Metrics\Metric;

/**
 * `value` is `null` when the metric does not apply to `symbol` (for
 * example, `ccn2` and `lines` on an abstract or interface method).
 */
final readonly class Measurement
{
    public function __construct(
        public string $symbol,
        public string $path,
        public int $line,
        public int $endLine,
        public Metric $metric,
        public ?int $value,
    ) {
    }
}
