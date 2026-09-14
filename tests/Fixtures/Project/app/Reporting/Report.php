<?php

declare(strict_types=1);

namespace Fixture\App\Reporting;

final class Report
{
    public function summarize(array $items): string
    {
        $out = '';

        foreach ($items as $item) {
            if ($item > 0) {
                $out .= $item.',';
            }
        }

        return $out;
    }
}
