<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Billing2;

final class Discount
{
    public function apply(int $amount, bool $active): int
    {
        if (! $active) {
            return $amount;
        }

        return $amount - 1;
    }
}
