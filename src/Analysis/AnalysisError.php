<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Analysis;

use RuntimeException;
use Throwable;

/**
 * Raised when a source file cannot be measured, for example because it
 * cannot be parsed. Carries the offending path so callers measuring many
 * files can report which one failed.
 */
final class AnalysisError extends RuntimeException
{
    public function __construct(
        public readonly string $path,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public static function unparsable(string $path, string $reason, ?Throwable $previous = null): self
    {
        return new self($path, "Could not parse \"{$path}\": {$reason}", $previous);
    }

    public static function unreadable(string $path): self
    {
        return new self($path, "Could not read \"{$path}\".");
    }
}
