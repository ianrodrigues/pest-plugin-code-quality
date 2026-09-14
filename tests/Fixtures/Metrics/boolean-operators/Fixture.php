<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\BooleanOperators;

final class Fixture
{
    // ccn2: 1 + && = 2
    public function andSymbol(bool $a, bool $b): bool
    {
        return $a && $b;
    }

    // ccn2: 1 + || = 2
    public function orSymbol(bool $a, bool $b): bool
    {
        return $a || $b;
    }

    // ccn2: 1 + and = 2
    public function andKeyword(bool $a, bool $b): bool
    {
        return $a and $b;
    }

    // ccn2: 1 + or = 2
    public function orKeyword(bool $a, bool $b): bool
    {
        return $a or $b;
    }

    // ccn2: 1 + xor = 2
    public function xorKeyword(bool $a, bool $b): bool
    {
        return $a xor $b;
    }

    // ccn2: 1 + && + || + and + or + xor = 6
    public function allOperators(bool $a, bool $b, bool $c, bool $d, bool $e): bool
    {
        $first = $a && $b;
        $second = $first || $c;
        $third = $second and $d;
        $fourth = $third or $e;

        return $fourth xor $a;
    }
}
