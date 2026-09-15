<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use IanRodrigues\CodeQuality\Analysis\Support\SymbolLocation;
use IanRodrigues\CodeQuality\Metrics\Metric;

/**
 * `ccn2` and `lines` are `null` when the method has no body (abstract and
 * interface methods); `params`, `methodName` and `variableName` always
 * apply. `variableName` is `0`, and `longestVariableIdentifier` is `null`,
 * when the method declares no variable at all.
 */
final readonly class MethodMeasurements
{
    /** Followed by the anonymous class declaration's line number. */
    public const string ANONYMOUS_SYMBOL_PREFIX = 'class@anonymous:';

    public string $symbol;

    public string $path;

    public int $line;

    public int $endLine;

    public function __construct(
        SymbolLocation $location,
        public ?int $ccn2,
        public ?int $lines,
        public int $params,
        public int $methodName,
        public int $variableName,
        public ?string $longestVariableIdentifier,
    ) {
        $this->symbol = $location->symbol;
        $this->path = $location->path;
        $this->line = $location->line;
        $this->endLine = $location->endLine;
    }

    /**
     * Anonymous-class methods are measured but have no stable, addressable
     * symbol, so they are never a valid target for the `Measurer` contract.
     */
    public function isAnonymous(): bool
    {
        return str_starts_with($this->symbol, self::ANONYMOUS_SYMBOL_PREFIX);
    }

    /**
     * A magic method (`__construct`, `__toString`, …) is exempt from
     * `methodName`: PHP dictates that name, so a project cannot rename it
     * to fit its own limit.
     */
    public function isMagicMethod(): bool
    {
        $separator = strrpos($this->symbol, '::');

        if ($separator === false) {
            return false;
        }

        return str_starts_with(substr($this->symbol, $separator + 2), '__');
    }

    /**
     * @return array{int|null, int|null, int, int, int} `[ccn2, lines, params, methodName, variableName]`
     */
    public function toArray(): array
    {
        return [$this->ccn2, $this->lines, $this->params, $this->methodName, $this->variableName];
    }

    /**
     * @return list<Measurement>
     */
    public function measurements(): array
    {
        return [
            new Measurement($this->symbol, $this->path, $this->line, $this->endLine, Metric::Ccn2, $this->ccn2),
            new Measurement($this->symbol, $this->path, $this->line, $this->endLine, Metric::Lines, $this->lines),
            new Measurement($this->symbol, $this->path, $this->line, $this->endLine, Metric::Params, $this->params),
            new Measurement(
                $this->symbol,
                $this->path,
                $this->line,
                $this->endLine,
                Metric::MethodName,
                $this->isMagicMethod() ? null : $this->methodName,
            ),
            new Measurement($this->symbol, $this->path, $this->line, $this->endLine, Metric::VariableName, $this->variableName),
        ];
    }
}
