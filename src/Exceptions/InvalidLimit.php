<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Exceptions;

use InvalidArgumentException;

final class InvalidLimit extends InvalidArgumentException
{
    public static function negative(string $description, int $limit): self
    {
        return new self(
            "{$description} limits must be zero or greater, but {$limit} was given.",
        );
    }
}
