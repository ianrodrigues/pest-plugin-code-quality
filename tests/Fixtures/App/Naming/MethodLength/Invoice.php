<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Naming\MethodLength;

final class Invoice
{
    public function __construct(private readonly int $id)
    {
    }

    public function total(): int
    {
        return $this->id;
    }

    public function calculateGrandTotal(): int
    {
        return $this->id;
    }
}
