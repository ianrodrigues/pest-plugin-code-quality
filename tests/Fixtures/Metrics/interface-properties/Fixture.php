<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InterfaceProperties;

interface HasCoordinates
{
    // PHP 8.4 lets an interface declare a property through a hook
    public float $x { get; }

    public float $y { get; }
}
