<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\NullCoalescing;

final class Fixture
{
    // ccn2: 1 + ?? = 2
    public function coalesce(?string $name): string
    {
        return $name ?? 'anonymous';
    }

    // ccn2: 1 + ??= = 2
    public function coalesceAssign(?string $name): string
    {
        $name ??= 'anonymous';

        return $name;
    }

    // ccn2: 1 + ?? + ?? + ??= = 4
    public function combined(?string $first, ?string $second, ?string $third): string
    {
        $third ??= 'fallback';

        return $first ?? $second ?? $third;
    }
}
