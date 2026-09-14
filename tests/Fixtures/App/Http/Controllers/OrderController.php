<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers;

final class OrderController
{
    public function show(string $id, bool $withLines = false): array
    {
        $order = ['id' => $id];

        if ($withLines) {
            $order['lines'] = [];
        }

        return $order;
    }

    public function cancel(string $id, ?string $reason = null): string
    {
        if ($reason === null) {
            return "cancelled {$id}";
        }

        return "cancelled {$id}: {$reason}";
    }
}
