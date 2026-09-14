<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\App\Legacy;

final class ImporterController
{
    public function import(array $rows, bool $strict, ?string $source): int // @pest-arch-ignore-line
    {
        $source = $source ?? 'csv';
        $imported = 0;

        foreach ($rows as $row) {
            if ($strict && ! isset($row['id'])) {
                continue;
            }

            if ($source === 'csv' || $source === 'tsv') {
                $imported++;
            }
        }

        return $imported;
    }
}
