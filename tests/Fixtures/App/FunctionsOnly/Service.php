<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\FunctionsOnly;

final class Service
{
    public function run(int $value): int
    {
        return double($value);
    }
}
