<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\Constructors;

final class Fixture
{
    // params: 3 (promoted parameters are counted like any other parameter)
    // ccn2: 1 (empty body, no counted construct)
    public function __construct(
        private readonly string $name,
        private readonly int $age,
        protected ?string $nickname = null,
    ) {
    }

    // ccn2: 1 (magic method with a trivial body)
    public function __toString(): string
    {
        return $this->name;
    }

    // ccn2: 1 + if = 2
    public function __get(string $property): mixed
    {
        if ($property === 'name') {
            return $this->name;
        }

        return null;
    }
}
