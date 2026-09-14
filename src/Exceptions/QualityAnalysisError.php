<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Exceptions;

use Rdgs\PestCodeQuality\Analysis\AnalysisError;
use RuntimeException;

/**
 * A file Pest couldn't measure is an engine problem, not a policy
 * violation, so this stays a plain `RuntimeException` rather than an
 * `AssertionFailedError`: the owning test ends as an error, never a
 * failure.
 */
final class QualityAnalysisError extends RuntimeException
{
    public static function fromAnalysisError(AnalysisError $error): self
    {
        return new self("Quality analysis error: {$error->getMessage()}", previous: $error);
    }
}
