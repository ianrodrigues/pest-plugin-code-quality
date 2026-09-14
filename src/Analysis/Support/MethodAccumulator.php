<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Analysis\Support;

/**
 * Mutable, in-progress state for one method while `MetricsVisitor` is still
 * traversing its body. `ccn2` accumulates as counted constructs are found;
 * everything else is fixed for the method's lifetime.
 */
final class MethodAccumulator
{
    public ?int $ccn2;

    public function __construct(
        public readonly string $symbol,
        public readonly int $line,
        public readonly int $endLine,
        public readonly int $params,
        public readonly bool $hasBody,
    ) {
        $this->ccn2 = $hasBody ? 1 : null;
    }
}
