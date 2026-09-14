<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\StringInterpolation;

final class Fixture
{
    private string $name = 'world';

    // ccn2: 1 (no counted construct)
    // lines: 2 (interpolated braces belong to the string, not to a block)
    public function simple(string $name): string
    {
        $greeting = "Hello {$name}";

        return $greeting;
    }

    // ccn2: 1 + if = 2
    // lines: 3 (the standalone closing brace of the if-block does not count)
    public function nested(array $row, bool $shout): string
    {
        if ($shout) {
            return "ROW {$row['id']} FOR {$this->name}";
        }

        return "row {$row['id']}";
    }

    // ccn2: 1 (no counted construct)
    // lines: 4 (every physical line of the heredoc counts, interpolation included)
    public function heredoc(string $name): string
    {
        return <<<TEXT
        Hello {$name},
        from {$this->name}.
        TEXT;
    }
}
