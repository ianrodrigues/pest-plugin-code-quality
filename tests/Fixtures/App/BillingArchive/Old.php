<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\App\BillingArchive;

final class Old
{
    public function reconcile(array $rows, bool $strict, ?string $ledger): int
    {
        $ledger = $ledger ?? 'default';
        $balance = 0;

        foreach ($rows as $row) {
            if ($strict && ! isset($row['amount'])) {
                continue;
            }

            $balance += $row['amount'] ?? 0;

            if ($balance > 1000 || $ledger === 'escrow') {
                $balance -= 1;
            }
        }

        return $balance;
    }
}
