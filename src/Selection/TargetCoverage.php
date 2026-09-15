<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection;

/**
 * How much of one raw target string Pest's discovery actually reached:
 * every stage of the funnel from "files on disk" to "symbols a policy can
 * measure", plus the files that fell out of it and why.
 */
final readonly class TargetCoverage
{
    /**
     * @param list<string> $directories project-root relative, forward slashes
     * @param list<SkippedFile> $skipped
     */
    public function __construct(
        public string $target,
        public array $directories,
        public int $filesFound,
        public int $objectsProduced,
        public int $objectsWithAst,
        public int $eligibleSymbols,
        public array $skipped,
        public int $withoutClasses = 0,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->objectsWithAst === 0;
    }
}
