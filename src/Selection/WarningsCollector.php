<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Selection;

/**
 * Every skipped file seen across the whole process, deduplicated by path so
 * the same file found by several policies is only reported once. Read by
 * `Plugins\OutputPlugin` at the end of the run.
 */
final class WarningsCollector
{
    /** @var array<string, SkippedFile> */
    private static array $files = [];

    private function __construct()
    {
    }

    /**
     * @param list<SkippedFile> $files
     */
    public static function record(array $files): void
    {
        foreach ($files as $file) {
            self::$files[$file->path] ??= $file;
        }
    }

    /**
     * @return list<SkippedFile>
     */
    public static function all(): array
    {
        $files = array_values(self::$files);

        usort($files, static fn (SkippedFile $a, SkippedFile $b): int => $a->path <=> $b->path);

        return $files;
    }

    public static function reset(): void
    {
        self::$files = [];
    }
}
