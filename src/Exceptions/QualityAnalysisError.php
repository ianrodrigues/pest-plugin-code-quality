<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Exceptions;

use IanRodrigues\CodeQuality\Analysis\AnalysisError;
use RuntimeException;

/**
 * A file Pest couldn't measure is an engine problem, not a policy
 * violation, so this stays a plain `RuntimeException` rather than an
 * `AssertionFailedError`: the test ends as an error, never a failure.
 */
final class QualityAnalysisError extends RuntimeException
{
    public static function fromAnalysisError(AnalysisError $error): self
    {
        return new self("Quality analysis error: {$error->getMessage()}", previous: $error);
    }
}
