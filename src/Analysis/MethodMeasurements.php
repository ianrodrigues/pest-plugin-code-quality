<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Analysis;

use Rdgs\PestCodeQuality\Metrics\MetricId;

/**
 * The `ccn2`, `lines` and `params` measurements for one method.
 *
 * `ccn2` and `lines` are `null` when the method has no body (abstract and
 * interface methods); `params` always applies.
 */
final readonly class MethodMeasurements
{
    /**
     * The prefix given to the symbol of a method declared on an anonymous
     * class, followed by the class declaration's line number.
     */
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
     * Whether `symbol` identifies a method declared on an anonymous class.
     *
     * Such methods are measured but have no stable, addressable symbol, so
     * they are never a valid target for the `Measurer` contract.
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
     * The individual metric measurements this method's row is made of.
     *
     * @return list<Measurement>
     */
    public function measurements(): array
    {
        return [
            new Measurement($this->symbol, $this->path, $this->line, $this->endLine, MetricId::ccn2(), $this->ccn2),
            new Measurement($this->symbol, $this->path, $this->line, $this->endLine, MetricId::lines(), $this->lines),
            new Measurement($this->symbol, $this->path, $this->line, $this->endLine, MetricId::params(), $this->params),
        ];
    }
}
