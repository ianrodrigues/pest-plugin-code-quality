<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Support\Concerns;

trait Sluggable
{
    public function slug(string $value, ?string $separator = null): string
    {
        $separator = $separator ?? '-';

        if ($value === '') {
            return '';
        }

        $slug = strtolower(trim($value));

        return str_replace(' ', $separator, $slug);
    }
}
