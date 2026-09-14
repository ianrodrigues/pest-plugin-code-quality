<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Selection;

use Rdgs\PestCodeQuality\Selection\Support\ResolvedDirectory;
use Rdgs\PestCodeQuality\Support\ProjectPath;
use RuntimeException;

/**
 * Vendor code carries no AST in Pest's architecture layer
 * (`VendorObjectDescription` never sets `stmts`), so a target that resolves
 * entirely under `vendor/` can never be measured. This is distinct from an
 * `EmptySelection`, which `allowEmpty` can opt out of: there is no limit
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
