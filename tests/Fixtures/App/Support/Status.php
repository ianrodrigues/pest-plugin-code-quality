<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Support;

enum Status: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Paid = 'paid';

    public function label(bool $short = false): string
    {
        if ($short) {
            return strtoupper($this->value[0]);
        }

        return match ($this) {
            self::Draft => 'Draft invoice',
            self::Open => 'Awaiting payment',
            self::Paid => 'Paid in full',
        };
    }
}
