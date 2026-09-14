<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClosuresAndArrowFunctions;

final class Fixture
{
    // ccn2: 1 + if (inside the closure) = 2
    public function filterPositive(array $numbers): array
    {
        return array_values(array_filter($numbers, function (int $n): bool {
            if ($n > 0) {
                return true;
            }

            return false;
        }));
    }

    // ccn2: 1 + && (inside the arrow function) = 2
    public function bothPositive(array $pairs): array
    {
        return array_filter($pairs, fn (array $pair): bool => $pair[0] > 0 && $pair[1] > 0);
    }

    // ccn2: 1 + ternary (closure) + ?? (arrow function) = 3
    public function combined(array $items): array
    {
        $labelFor = function (int $item): string {
            return $item > 0 ? 'positive' : 'non-positive';
        };

        $withDefault = fn (?string $value): string => $value ?? 'default';

        return [$labelFor(1), $withDefault(null)];
    }
}
