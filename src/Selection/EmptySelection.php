<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection;

use RuntimeException;

/**
 * Thrown instead of letting a policy pass having measured nothing. Opt out
 * per expectation with `allowEmpty: true` when that is genuinely expected.
 */
final class EmptySelection extends RuntimeException
{
    public static function for(string $target, TargetCoverage $coverage): self
    {
        $directories = $coverage->directories === []
            ? '(none resolved)'
            : implode(', ', $coverage->directories);

        return new self(sprintf(
            '"%s" matched no classes to measure.'."\n"
            .'Directories searched: %s'."\n"
            .'PHP files found: %d'."\n"
            .'Objects loadable: %d'."\n"
            .'If an empty selection is expected here, pass allowEmpty: true to the expectation.',
            $target,
            $directories,
            $coverage->filesFound,
            $coverage->objectsWithAst,
        ));
    }
}
