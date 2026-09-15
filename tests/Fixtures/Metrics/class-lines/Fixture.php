<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassLines;

final class Fixture
{
    // classLines: a comment line never counts

    public function build(bool $flag): string
    {
        if ($flag) {
            return <<<TEXT
                first
                last
                TEXT;
        }

        return 'plain';
    }

    // classLines: every line of the nested declaration counts, its braces aside
    public function nested(): object
    {
        return new class
        {
            public function value(): int
            {
                return 1;
            }
        };
    }
}
