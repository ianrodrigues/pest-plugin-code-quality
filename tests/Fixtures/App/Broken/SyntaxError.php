<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Broken;

final class SyntaxError
{
    public function broken(): void
    {
        if (true) {
    }
}
