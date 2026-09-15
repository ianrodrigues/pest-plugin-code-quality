<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Reporting;

use IanRodrigues\CodeQuality\Support\ProjectPath;
use Pest\Support\HigherOrderMessage;

/**
 * Where a quality expectation was declared, captured eagerly: by the
 * time an architecture expectation actually verifies, the call stack no
 * longer points at the `->toHaveMethodXAtMost()` call site.
 */
final readonly class PolicyLocation
{
    private function __construct(
        public string $file,
        public int $line,
    ) {
    }

    /**
     * Walks the call stack past this package's own source and vendor to the
     * first project frame — but a top-level `arch(...)` chain replays via a
     * `HigherOrderMessage`, which carries the real call site and wins when present.
     */
    public static function capture(): self
    {
        $ownSource = dirname(__DIR__).DIRECTORY_SEPARATOR;

        foreach (debug_backtrace() as $frame) {
            $object = $frame['object'] ?? null;

            if ($object instanceof HigherOrderMessage) {
                return new self(ProjectPath::relative($object->filename), $object->line);
            }

            $file = $frame['file'] ?? null;

            if (! is_string($file)) {
                continue;
            }

            if (str_starts_with($file, $ownSource)) {
                continue;
            }

            if (str_contains($file, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            return new self(ProjectPath::relative($file), $frame['line'] ?? 0);
        }

        return new self('', 0);
    }
}
