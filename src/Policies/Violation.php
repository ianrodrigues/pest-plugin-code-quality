<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Policies;

use IanRodrigues\CodeQuality\Analysis\Contribution;
use IanRodrigues\CodeQuality\Metrics\Metric;

final readonly class Violation
{
    /**
     * `$path` is relative to the project root with forward slashes,
     * `$line` is the method or class-like declaration line, and
     * `$accepted` is the ceiling a baseline entry raised, if one applied.
     */
    public function __construct(
        public string $symbol,
        public string $path,
        public int $line,
        public Metric $metric,
        public int $value,
        public int $limit,
        public ?int $accepted = null,
        /** @var list<Contribution> */
        public array $contributions = [],
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
