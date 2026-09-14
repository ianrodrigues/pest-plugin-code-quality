<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use IanRodrigues\CodeQuality\Metrics\Metric;

/**
 * `ccn2` and `lines` are `null` when the method has no body (abstract and
 * interface methods); `params` always applies.
 */
final readonly class MethodMeasurements
{
    /** Followed by the anonymous class declaration's line number. */
    public const string ANONYMOUS_SYMBOL_PREFIX = 'class@anonymous:';

    public function __construct(
        public string $symbol,
        public string $path,
        public int $line,
        public int $endLine,
        public ?int $ccn2,
        public ?int $lines,
        public int $params,
    ) {
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
     * @return array{int|null, int|null, int} `[ccn2, lines, params]`
     */
    public function toArray(): array
    {
        return [$this->ccn2, $this->lines, $this->params];
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
        ];
    }
}
