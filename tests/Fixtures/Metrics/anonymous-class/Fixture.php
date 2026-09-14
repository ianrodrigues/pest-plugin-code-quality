<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\AnonymousClass;

final class Fixture
{
    // ccn2: 1 (the anonymous class's own control flow never contributes to the
    // enclosing method; its methods are measured separately and are never a
    // target). All physical lines of the anonymous class declaration are
    // still part of this method's `lines` count because they sit inside its
    // braces.
    public function makeGreeter(): object
    {
        return new class {
            public function greet(bool $formal): string
            {
                if ($formal) {
                    return 'Good day.';
                }

                return 'hi';
            }
        };
    }
}
