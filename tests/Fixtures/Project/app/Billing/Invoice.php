<?php

declare(strict_types=1);

namespace Fixture\App\Billing;

final class Invoice
{
    public function total(array $lines): int
    {
        $total = 0;

        foreach ($lines as $line) {
            $total += $line;
        }

        return $total;
    }
}
