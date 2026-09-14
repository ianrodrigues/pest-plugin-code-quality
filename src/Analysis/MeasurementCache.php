<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use Closure;

/**
 * Keyed by real path and a content hash, so changed content is never
 * served stale.
 */
final class MeasurementCache
{
    /** @var array<string, FileMeasurements> */
    private array $entries = [];

    /**
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
