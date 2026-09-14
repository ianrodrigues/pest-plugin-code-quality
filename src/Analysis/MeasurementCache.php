<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Analysis;

use Closure;

/**
 * An in-memory, per-process cache of `FileMeasurements`, keyed by the
 * measured file's real path and the hash of the content that produced it.
 *
 * Unchanged content is never re-measured; changed content always is.
 */
final class MeasurementCache
{
    /** @var array<string, FileMeasurements> */
    private array $entries = [];

    /**
     * Returns the cached measurements for `$path`/`$contents`, computing
     * and storing them via `$compute` on a cache miss.
     *
     * @param Closure(): FileMeasurements $compute
     */
    public function remember(string $path, string $contents, Closure $compute): FileMeasurements
    {
        $key = $this->key($path, $contents);

        return $this->entries[$key] ??= $compute();
    }

    private function key(string $path, string $contents): string
    {
        $realPath = realpath($path);
        $identity = $realPath !== false ? $realPath : $path;

        return $identity . '#' . hash('xxh128', $contents);
    }
}
