<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\FunctionsOnly;

// Declares no class, interface, trait or enum, like `src/Autoload.php` in a
// real project: there is nothing here for a policy to measure.
function double(int $value): int
{
    return $value * 2;
}
