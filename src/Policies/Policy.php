<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Policies;

use LogicException;
use Rdgs\PestCodeQuality\Analysis\MethodMeasurements;
use Rdgs\PestCodeQuality\Exceptions\InvalidLimit;
use Rdgs\PestCodeQuality\Metrics\MetricId;

final readonly class Policy
{
    private function __construct(
        public MetricId $metric,
        public int $limit,
        public string $description,
        public bool $allowEmpty = false,
    ) {
    }

    public static function complexity(int $limit, bool $allowEmpty = false): self
    {
        return self::make(MetricId::ccn2(), $limit, 'Method complexity', $allowEmpty);
    }

    public static function lines(int $limit, bool $allowEmpty = false): self
    {
        return self::make(MetricId::lines(), $limit, 'Method lines', $allowEmpty);
    }

    public static function parameters(int $limit, bool $allowEmpty = false): self
    {
        return self::make(MetricId::params(), $limit, 'Method parameters', $allowEmpty);
    }

    /**
     * Null when the metric does not apply to the method, as `ccn2` and
     * `lines` do not to abstract and interface methods.
     */
    public function valueFor(MethodMeasurements $method): ?int
    {
        return match ($this->metric->name) {
            'ccn2' => $method->ccn2,
            'lines' => $method->lines,
            'params' => $method->params,
            default => throw new LogicException(
                "No measurement is exposed for the \"{$this->metric->name}\" metric.",
            ),
        };
    }

    /**
     * Limits are inclusive: a value equal to the limit is allowed.
     */
    public function allows(int $value): bool
    {
        return $value <= $this->limit;
    }

    public function metricLabel(): string
    {
        return "{$this->metric->name} v{$this->metric->version}";
    }

    private static function make(MetricId $metric, int $limit, string $description, bool $allowEmpty): self
    {
        if ($limit < 0) {
            throw InvalidLimit::negative($description, $limit);
        }

        return new self($metric, $limit, $description, $allowEmpty);
    }
}
