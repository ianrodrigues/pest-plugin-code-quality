<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Selection;

use RuntimeException;

/**
 * Thrown instead of warning when `Config::strict()` is on: a skipped file
 * is treated as a failure of the run, not a note in the summary.
 */
final class SkippedFilesFound extends RuntimeException
{
    /**
     * @param list<SkippedFile> $files
     */
    public static function for(array $files): self
    {
        $lines = array_map(
            static fn (SkippedFile $file): string => "  {$file->path} ({$file->reason->label()})",
            $files,
        );

        return new self(sprintf(
            "%d file(s) were found but not analysed:\n%s",
            count($files),
            implode("\n", $lines),
        ));
    }
}
