<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\Switch_;

final class Fixture
{
    // ccn2: 1 + case + case = 3 (switch itself and default do not count)
    public function label(int $code): string
    {
        switch ($code) {
            case 1:
                return 'one';
            case 2:
                return 'two';
            default:
                return 'other';
        }
    }

    // ccn2: 1 + case = 2 (no default present)
    public function partial(int $code): string
    {
        switch ($code) {
            case 1:
                return 'one';
        }

        return 'fallback';
    }
}
