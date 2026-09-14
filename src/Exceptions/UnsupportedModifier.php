<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Exceptions;

use LogicException;

final class UnsupportedModifier extends LogicException
{
    public const string NOT = 'Numeric limits cannot be negated with `not`; choose a lower limit instead.';

    public static function not(): self
    {
        return new self(self::NOT);
    }
}
