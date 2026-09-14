<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\Traits;

trait Greetable
{
    // ccn2: 1 + if = 2 (attributed to the trait, never to the using class)
    public function greet(bool $formal): string
    {
        if ($formal) {
            return 'Good day.';
        }

        return 'hi';
    }
}

final class Fixture
{
    use Greetable;
}
