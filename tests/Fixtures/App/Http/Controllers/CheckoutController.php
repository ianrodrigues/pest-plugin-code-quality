<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers;

final class CheckoutController
{
    public function index(): string
    {
        return 'checkout';
    }

    public function store(
        array $cart,
        ?string $coupon,
        ?string $currency,
        bool $express,
        ?array $address,
        ?string $note,
        bool $dryRun
    ): array {
        $currency = $currency ?? 'usd';
        $note = $note ?? '';
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

            if ($express || $dryRun) {
                $total += 5;
            }
        }

        if ($coupon !== null) {
            $discount = match ($coupon) {
                'welcome' => 10,
                'loyalty' => 20,
                'staff' => 50,
                default => 0,
            };

            $total -= $discount;
        }

        while ($total < 0) {
            $total = 0;
        }

        try {
            $reference = $this->reference($address, $currency);
        } catch (\RuntimeException) {
            $reference = 'unknown';
        }

        $shipping = $express ? 15 : 5;
        $label = $address['label'] ?? 'default';

        switch ($label) {
            case 'home':
                $shipping += 1;
                break;
            case 'work':
                $shipping += 2;
                break;
        }

        return [
            'reference' => $reference,
            'currency' => $currency,
            'lines' => $lines,
            'note' => $note,
            'total' => $total + $shipping,
            'dry_run' => $dryRun,
        ];
    }

    private function reference(?array $address, string $currency): string
    {
        return strtoupper($currency).'-'.count($address ?? []);
    }
}
