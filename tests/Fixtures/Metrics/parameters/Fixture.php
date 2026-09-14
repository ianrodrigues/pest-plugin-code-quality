<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\Parameters;

final class Fixture
{
    // params: 2 (both required)
    public function required(int $a, string $b): void
    {
    }

    // params: 2 (both optional)
    public function optional(int $a = 1, string $b = 'x'): void
    {
    }

    // params: 2 (one required, one variadic)
    public function variadic(int $first, int ...$rest): void
    {
    }

    // params: 1 (by-reference)
    public function byRef(int &$value): void
    {
    }

    // params: 4 (required, required-by-ref, optional, variadic; each counted once)
    public function combined(int $required, int &$byRef, string $optional = 'x', ...$rest): void
    {
    }

    // params: 3 (promoted parameters count the same as any other declared parameter)
    public function __construct(
        private int $a,
        protected int $b,
        public int $c,
    ) {
    }
}
