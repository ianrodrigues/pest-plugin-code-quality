<?php

declare(strict_types=1);

// Deliberately declares a namespace other than the one its directory
// implies, so a target of "...\GhostNamespace" finds this file but no
// eligible method: a regression fixture for namespace-boundary detection.
namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\ActualNamespace;

final class Service
{
    public function run(): int
    {
        return 1;
    }
}
