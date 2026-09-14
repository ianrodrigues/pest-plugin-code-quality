<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\Ternary;

final class Fixture
{
    // ccn2: 1 + ternary = 2
    public function fullTernary(int $x): string
    {
        return $x > 0 ? 'positive' : 'non-positive';
    }

    // ccn2: 1 + short ternary = 2
    public function shortTernary(?string $name): string
    {
        return $name ?: 'anonymous';
    }

    // ccn2: 1 + ternary + short ternary = 3
    public function both(int $x, ?string $name): string
    {
        $sign = $x > 0 ? 'positive' : 'negative';
        $label = $name ?: 'anonymous';

        return $sign . ':' . $label;
    }
}
