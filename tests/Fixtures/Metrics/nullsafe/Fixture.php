<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\Nullsafe;

final class Address
{
    public ?string $city = null;

    public function city(): ?string
    {
        return $this->city;
    }
}

final class Fixture
{
    // ccn2: 1 + ?-> (property) = 2
    public function propertyAccess(?Address $address): ?string
    {
        return $address?->city;
    }

    // ccn2: 1 + ?-> (method) = 2
    public function methodAccess(?Address $address): ?string
    {
        return $address?->city();
    }

    // ccn2: 1 + ?-> (property) + ?-> (method) = 3
    public function combined(?Address $address): string
    {
        $city = $address?->city;
        $fromMethod = $address?->city();

        return $city . $fromMethod;
    }
}
