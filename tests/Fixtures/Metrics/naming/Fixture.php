<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\Naming;

// className: Widget = 6
final class Widget
{
    // methodName: __construct = 11 (a magic method still measures a real length)
    // variableName: sku = 3 (a promoted property is a declaration site)
    public function __construct(private readonly string $sku)
    {
    }
}

// className: NoVariables = 11
final class NoVariables
{
    // methodName: label = 5; variableName: 0 (the method declares no variable)
    public function label(): string
    {
        return 'widget';
    }

    // methodName: x = 1 (boundary: the shortest possible method name)
    public function x(): void
    {
    }
}

// className: Boundary = 8
final class Boundary
{
    // variableName: years = 5, longer than the one-letter $y
    public function pick(int $y, int $years): int
    {
        return $y + $years;
    }
}

// className: Tally = 5
final class Tally
{
    // ccn2: 1 + foreach = 2
    // variableName: rowValue = 8
    public function totals(array $rows): int
    {
        $total = 0;

        foreach ($rows as $rowKey => $rowValue) {
            $total += $rowValue;
        }

        return $total;
    }
}

// className: Guard = 5
final class Guard
{
    // ccn2: 1 + catch = 2
    // variableName: divisionError = 13
    public function safeDivide(int $numerator, int $denominator): float
    {
        try {
            return $numerator / $denominator;
        } catch (\DivisionByZeroError $divisionError) {
            return 0.0;
        }
    }
}

// className: Scaler = 6
final class Scaler
{
    // variableName: multiplier = 10, a closure parameter longer than its use variable
    public function scale(int $value): \Closure
    {
        $factor = 2;

        return function (int $multiplier) use ($factor): int {
            return $multiplier * $factor;
        };
    }
}

// className: Greeter = 7
final class Greeter
{
    // variableName: café = 4, counted by character, not by byte
    public function greet(): string
    {
        $café = 'espresso';

        return $café;
    }
}

// className: Café = 4, counted by character, not by byte
final class Café
{
}

// className: Q = 1 (boundary: the shortest possible class name)
final class Q
{
}
