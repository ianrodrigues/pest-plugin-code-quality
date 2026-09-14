<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Exceptions;

use LogicException;
use NunoMaduro\Collision\Contracts\RenderableOnCollisionEditor;
use PHPUnit\Framework\AssertionFailedError;
use Rdgs\PestCodeQuality\Policies\PolicyResult;
use Rdgs\PestCodeQuality\Policies\Violation;
use Rdgs\PestCodeQuality\Reporting\FailureReport;
use Whoops\Exception\Frame;

/**
 * `Pest\Arch\Exceptions\ArchExpectationFailedException` is final, so this
 * carries its base class and its Collision contract rather than extending
 * it.
 */
final class QualityExpectationFailed extends AssertionFailedError implements RenderableOnCollisionEditor
{
    private function __construct(
        private readonly Violation $reference,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function fromResult(PolicyResult $result): self
    {
        $violations = $result->sortedViolations();

        if ($violations === []) {
            throw new LogicException('A passing policy result cannot be reported as a failure.');
        }

        return new self($violations[0], FailureReport::for($result));
    }

    public function toCollisionEditor(): Frame
    {
        return new Frame([
            'file' => $this->reference->path,
            'line' => $this->reference->line,
        ]);
    }
}
