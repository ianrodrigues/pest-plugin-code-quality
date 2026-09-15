<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Naming\VariableLength;

final class Order
{
    public function total(int $quantity, float $unitPrice): float
    {
        $someVeryLongVariableName = $quantity * $unitPrice;

        return $someVeryLongVariableName;
    }
}
