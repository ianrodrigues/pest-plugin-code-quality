<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Policies;

use Rdgs\PestCodeQuality\Metrics\MetricId;

final readonly class Violation
{
    /**
     * `$path` is relative to the project root with forward slashes, and
     * `$line` is the method declaration's line.
     */
    public function __construct(
        public string $symbol,
        public string $path,
        public int $line,
        public MetricId $metric,
        public int $value,
        public int $limit,
    ) {
    }

    public function excess(): int
    {
        return $this->value - $this->limit;
    }
}
