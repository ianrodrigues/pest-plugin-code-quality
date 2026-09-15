<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure;

interface Contract
{
    public function handle(int $value): bool;

    public function name(): string;
}
