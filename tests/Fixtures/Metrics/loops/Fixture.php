<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\Loops;

final class Fixture
{
    // ccn2: 1 + for = 2
    public function countUp(int $limit): int
    {
        $total = 0;

        for ($i = 0; $i < $limit; $i++) {
            $total += $i;
        }

        return $total;
    }

    // ccn2: 1 + foreach = 2
    public function sum(array $numbers): int
    {
        $total = 0;

        foreach ($numbers as $number) {
            $total += $number;
        }

        return $total;
    }

    // ccn2: 1 + while = 2
    public function countDown(int $start): int
    {
        $steps = 0;

        while ($start > 0) {
            $start--;
            $steps++;
        }

        return $steps;
    }

    // ccn2: 1 + do = 2
    public function runOnce(int $value): int
    {
        $iterations = 0;

        do {
            $value--;
            $iterations++;
        } while ($value > 0);

        return $iterations;
    }

    // ccn2: 1 + for + foreach + while = 4
    public function combined(array $rows): int
    {
        $total = 0;

        for ($pass = 0; $pass < 2; $pass++) {
            $total++;
        }

        foreach ($rows as $row) {
            $total += $row;
        }

        while ($total > 1000) {
            $total--;
        }

        return $total;
    }
}
