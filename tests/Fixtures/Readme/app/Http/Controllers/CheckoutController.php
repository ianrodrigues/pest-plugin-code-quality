<?php

declare(strict_types=1);

namespace App\Http\Controllers;

final class CheckoutController
{
    public function store(array $cart, ?string $coupon, bool $express): array
    {
        $total = 0;
        $lines = [];

        foreach ($cart as $item) {
            if (! isset($item['sku'])) {
                continue;
            }

            $quantity = $item['quantity'] ?? 1;
            $price = $item['price'] ?? 0;

            if ($quantity > 0 && $price > 0) {
                $total += $quantity * $price;
                $lines[] = $item['sku'];
            }

            if ($express) {
                $total += 5;
            }
        }

        if ($coupon !== null) {
            $discount = match ($coupon) {
                'welcome' => 10,
                'loyalty' => 20,
                default => 0,
            };

            $total -= $discount;
        }

        return ['total' => $total, 'lines' => $lines];
    }
}
