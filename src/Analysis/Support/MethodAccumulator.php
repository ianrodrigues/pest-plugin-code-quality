<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

/**
 * Mutable, in-progress state for one method while `MetricsVisitor` is still
 * traversing its body. `ccn2` accumulates as counted constructs are found;
 * `variableName` and `longestVariableIdentifier` track the longest
 * declared variable seen so far, keeping the first one found on a tie;
 * everything else is fixed for the method's lifetime.
 */
final class MethodAccumulator
{
    public ?int $ccn2;

    public int $variableName = 0;

    public ?string $longestVariableIdentifier = null;

    public function __construct(
        public readonly string $symbol,
        public readonly int $line,
        public readonly int $endLine,
        public readonly int $params,
        public readonly bool $hasBody,
    ) {
        $this->ccn2 = $hasBody ? 1 : null;
    }

    public function recordVariable(string $name): void
    {
        $length = mb_strlen($name);

        if ($length > $this->variableName) {
            $this->variableName = $length;
            $this->longestVariableIdentifier = $name;
        }
    }
}
