<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Selection;

/**
 * A file found under a target's resolved directories that Pest's
 * architecture layer never turned into a measurable object.
 */
final readonly class SkippedFile
{
    /**
     * `$path` is project-root relative with forward slashes.
     */
    public function __construct(
        public string $path,
        public SkipReason $reason,
    ) {
    }
}
