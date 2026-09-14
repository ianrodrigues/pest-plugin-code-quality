<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\App\PartialLoad;

final class Fine
{
    public function total(int $a, int $b): int
    {
        return $a + $b;
    }
}
