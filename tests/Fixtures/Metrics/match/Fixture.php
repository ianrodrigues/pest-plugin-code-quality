<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\Match_;

final class Fixture
{
    // ccn2: 1 + arm + arm = 3 (match itself and the default arm do not count)
    public function label(int $code): string
    {
        return match ($code) {
            1 => 'one',
            2 => 'two',
            default => 'other',
        };
    }

    // ccn2: 1 + arm + arm = 3 (both arms have explicit conditions, no default present)
    public function exhaustive(bool $flag): string
    {
        return match ($flag) {
            true => 'yes',
            false => 'no',
        };
    }

    // ccn2: 1 + arm (with comma-separated conditions counts once per arm) + arm = 3
    public function grouped(int $day): string
    {
        return match ($day) {
            1, 2, 3, 4, 5 => 'weekday',
            6, 7 => 'weekend',
            default => 'invalid',
        };
    }
}
