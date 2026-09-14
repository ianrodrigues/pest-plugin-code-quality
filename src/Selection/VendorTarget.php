<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection;

use IanRodrigues\CodeQuality\Selection\Support\ResolvedDirectory;
use IanRodrigues\CodeQuality\Support\ProjectPath;
use RuntimeException;

/**
 * Vendor code carries no AST in Pest's architecture layer, so a target
 * resolving entirely under `vendor/` can never be measured — unlike
 * `EmptySelection`, which `allowEmpty` opts out of, there is no limit
 * worth setting here at all.
 */
final class VendorTarget extends RuntimeException
{
    /**
     * @param list<ResolvedDirectory> $directories
     */
    public static function for(string $target, array $directories): self
    {
        $paths = implode(', ', array_map(
            static fn (ResolvedDirectory $directory): string => ProjectPath::relative($directory->path),
            $directories,
        ));

        return new self(sprintf(
            '"%s" resolves under vendor/ (%s). Pest\'s architecture layer produces no AST for vendor '
            .'code, so it cannot be analysed.',
            $target,
            $paths,
        ));
    }
}
