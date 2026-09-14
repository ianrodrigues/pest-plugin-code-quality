<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\Lines;

final class Fixture
{
    // ccn2: 1 (no counted construct)
    // lines: 3 (blank lines and a comment-only line do not count)
    public function withBlankLinesAndComments(): int
    {
        $a = 1;

        // just a comment, contributes nothing
        $b = 2;

        return $a + $b;
    }

    // ccn2: 1 (no counted construct)
    // lines: 1 (three statements on one physical line count once)
    public function multiStatementLine(): int
    {
        $a = 1; $b = 2; return $a + $b;
    }

    // ccn2: 1 (no counted construct)
    // lines: 5 (each physical line occupied by the heredoc counts; the blank
    // line before the return does not)
    public function withHeredoc(): string
    {
        $text = <<<TEXT
        Hello
        World
        TEXT;

        return $text;
    }

    // ccn2: 1 + if = 2
    // lines: 3 (a standalone closing brace from the nested if-block does not
    // count; only the `if (...)` line and the two statement lines do)
    public function withBraceOnlyLine(bool $flag): int
    {
        if ($flag) {
            return 1;
        }

        return 0;
    }
}
