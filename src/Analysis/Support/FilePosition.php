<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use LogicException;
use PhpParser\Node;

/**
 * Byte offsets are attributes rather than typed properties on a node, so
 * every read of one has to prove it is an `int`.
 */
final class FilePosition
{
    private function __construct()
    {
    }

    public static function of(Node $node, string $attribute): int
    {
        $value = $node->getAttribute($attribute);

        if (! is_int($value)) {
            throw new LogicException("Node is missing its \"{$attribute}\" attribute.");
        }

        return $value;
    }
}
