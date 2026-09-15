<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use IanRodrigues\CodeQuality\Analysis\Contribution;

/**
 * Mutable, in-progress state for one method while `MetricsVisitor` is still
 * traversing its body. `ccn2` and `ccn2Contributions` grow as counted
 * constructs are found; `variableName` and `longestVariableIdentifier`
 * track the longest declared variable seen so far, keeping the first one
 * found on a tie; everything else is fixed for the method's lifetime.
 */
final class MethodAccumulator
{
    public ?int $ccn2;

    /** @var list<Contribution> */
    public array $ccn2Contributions = [];

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

    /** A no-op on a bodiless method, whose `ccn2` stays `null`. */
    public function recordConstruct(string $label, int $line): void
    {
        if ($this->ccn2 === null) {
            return;
        }

        $this->ccn2Contributions[] = new Contribution($label, $line);
        $this->ccn2++;
    }
}
