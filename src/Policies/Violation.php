<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Policies;

use IanRodrigues\CodeQuality\Metrics\Metric;

final readonly class Violation
{
    /**
     * `$path` is relative to the project root with forward slashes,
     * `$line` is the method declaration's line, and `$accepted` is the
     * ceiling a baseline entry raised, when one applied to this symbol.
     */
    public function __construct(
        public string $symbol,
        public string $path,
        public int $line,
        public Metric $metric,
        public int $value,
        public int $limit,
        public ?int $accepted = null,
    ) {
    }

    public function excess(): int
    {
        return $this->value - $this->limit;
    }

    /** What a raised ceiling is actually failing on. */
    public function increase(): ?int
    {
        return $this->accepted === null ? null : $this->value - $this->accepted;
    }
}
