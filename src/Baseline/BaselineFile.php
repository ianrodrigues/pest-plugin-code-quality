<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Metrics\Metric;

use const IanRodrigues\CodeQuality\VERSION;

use JsonException;
use ValueError;

/**
 * Reads and writes the baseline document described by
 * `schema/quality-baseline.v1.json`; a document that does not match the
 * schema is always an error, never partially honoured.
 */
final class BaselineFile
{
    private function __construct()
    {
    }

    public static function read(string $path): Baseline
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw BaselineError::missing($path);
        }

        $contents = (string) file_get_contents($path);

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw BaselineError::malformed($path, $exception->getMessage());
        }

        if (! is_array($decoded)) {
            throw BaselineError::malformed($path, 'the document is not an object');
        }

        if (($decoded['schemaVersion'] ?? null) !== Baseline::SCHEMA_VERSION) {
            throw BaselineError::malformed(
                $path,
                'expected schemaVersion '.Baseline::SCHEMA_VERSION,
            );
        }

        $entries = $decoded['entries'] ?? null;

        if (! is_array($entries)) {
            throw BaselineError::malformed($path, 'entries is missing or is not a list');
        }

        return Baseline::of(array_map(
            static fn (mixed $entry): BaselineEntry => self::entry($path, $entry),
            array_values($entries),
        ));
    }

    /**
     * Written through a temporary file in the same directory and renamed
     * over the target, so an interrupted or failed write leaves whatever
     * was already there untouched rather than a truncated document.
     */
    public static function write(string $path, Baseline $baseline): void
    {
        self::ensureWritable($path);

        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';

        if (file_put_contents($temporary, self::encode($baseline)) === false) {
            throw BaselineError::unwritable($path);
        }

        if (! rename($temporary, $path)) {
            if (is_file($temporary)) {
                unlink($temporary);
            }

            throw BaselineError::unwritable($path);
        }
    }

    private static function ensureWritable(string $path): void
    {
        $directory = dirname($path);
        $parent = dirname($directory);

        if (! is_dir($directory) && is_dir($parent) && is_writable($parent)) {
            mkdir($directory, 0777, true);
        }

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw BaselineError::unwritable($path);
        }
    }

    private static function encode(Baseline $baseline): string
    {
        $document = [
            'schemaVersion' => Baseline::SCHEMA_VERSION,
            'generatedAt' => gmdate('Y-m-d\TH:i:s\Z'),
            'pluginVersion' => VERSION,
            'entries' => array_map(
                static fn (BaselineEntry $entry): array => $entry->toArray(),
                $baseline->entries,
            ),
        ];

        return json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    private static function entry(string $path, mixed $entry): BaselineEntry
    {
        if (! is_array($entry)) {
            throw BaselineError::malformed($path, 'an entry is not an object');
        }

        $metric = $entry['metric'] ?? null;

        if (! is_array($metric) || ! is_string($metric['name'] ?? null) || ! is_int($metric['version'] ?? null)) {
            throw BaselineError::malformed($path, 'an entry has no valid metric');
        }

        try {
            $name = Metric::from($metric['name']);
        } catch (ValueError) {
            throw BaselineError::malformed($path, 'an entry has no valid metric');
        }

        foreach (['policy', 'symbol', 'path'] as $key) {
            if (! is_string($entry[$key] ?? null)) {
                throw BaselineError::malformed($path, "an entry has no valid {$key}");
            }
        }

        foreach (['limit', 'accepted'] as $key) {
            if (! is_int($entry[$key] ?? null)) {
                throw BaselineError::malformed($path, "an entry has no valid {$key}");
            }
        }

        /** @var array{policy: string, symbol: string, limit: int, accepted: int, path: string} $entry */
        return new BaselineEntry(
            $entry['policy'],
            $entry['symbol'],
            $name,
            $metric['version'],
            $entry['limit'],
            $entry['accepted'],
            $entry['path'],
        );
    }
}
