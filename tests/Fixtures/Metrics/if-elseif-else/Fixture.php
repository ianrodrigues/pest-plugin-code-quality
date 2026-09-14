<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\IfElseifElse;

final class Fixture
{
    // ccn2: 1 + if = 2
    public function onlyIf(int $x): int
    {
        if ($x > 0) {
            return 1;
        }

        return 0;
    }

    // ccn2: 1 + if + elseif = 3 (else does not count)
    public function graded(int $score): string
    {
        if ($score >= 90) {
            return 'A';
        } elseif ($score >= 80) {
            return 'B';
        } else {
            return 'C';
        }
    }

    // ccn2: 1 + if + elseif + elseif = 4 (else does not count)
    public function multiway(int $level): string
    {
        if ($level === 1) {
            $label = 'low';
        } elseif ($level === 2) {
            $label = 'medium';
        } elseif ($level === 3) {
            $label = 'high';
        } else {
            $label = 'unknown';
        }

        return $label;
    }
}
