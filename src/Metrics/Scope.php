<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Metrics;

/**
 * What a metric measures: one method, or one class-like declaration. The
 * scope decides which measurements a policy compares against its limit.
 */
enum Scope: string
{
    case Method = 'method';
    case ClassLike = 'class';

    public function plural(): string
    {
        return match ($this) {
            self::Method => 'methods',
            self::ClassLike => 'classes',
        };
    }
}
