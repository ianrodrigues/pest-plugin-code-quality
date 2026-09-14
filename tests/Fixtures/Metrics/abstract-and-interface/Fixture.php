<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\AbstractAndInterface;

interface Processor
{
    // ineligible for ccn2/lines (no body); params are still counted
    public function process(int $value, string $label): bool;
}

abstract class Fixture
{
    // ineligible for ccn2/lines (no body); params are still counted
    abstract public function handle(int $value): void;

    // ccn2: 1 + if = 2 (a concrete method on an abstract class is eligible)
    public function describe(int $value): string
    {
        if ($value > 0) {
            return 'positive';
        }

        return 'non-positive';
    }
}
