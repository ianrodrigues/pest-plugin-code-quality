<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth;

use PhpParser\NodeVisitorAbstract;
use RuntimeException;

interface Shape
{
    public function area(): float;
}

// inheritance: an implemented interface is not a parent = 0
class Root implements Shape
{
    public function area(): float
    {
        return 0.0;
    }
}

// inheritance: Root = 1
class Middle extends Root
{
}

// inheritance: Middle + Root = 2
class Leaf extends Middle
{
}

// inheritance: Leaf + Middle + Root = 3
final class Fixture extends Leaf
{
}

// inheritance: RuntimeException + Exception = 2, both resolved by reflection
final class Reported extends RuntimeException
{
}

// inheritance: a parent outside the measured source counts as one = 1
final class Visiting extends NodeVisitorAbstract
{
}
