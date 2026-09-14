<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\Enums;

enum Suit: string
{
    case Hearts = 'H';
    case Diamonds = 'D';
    case Clubs = 'C';
    case Spades = 'S';

    // ccn2: 1 + arm + arm = 3 (no default arm present)
    public function color(): string
    {
        return match ($this) {
            self::Hearts, self::Diamonds => 'red',
            self::Clubs, self::Spades => 'black',
        };
    }

    // ccn2: 1 + if + || = 3
    public function isRed(): bool
    {
        if ($this === self::Hearts || $this === self::Diamonds) {
            return true;
        }

        return false;
    }
}
