<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Policies;

use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Exceptions\InvalidLimit;
use IanRodrigues\CodeQuality\Metrics\Metric;

final readonly class Policy
{
    public string $description;

    public string $expectation;

    private function __construct(
        public Metric $metric,
        public int $limit,
        public bool $allowEmpty = false,
    ) {
        $this->description = $metric->label();
        $this->expectation = $metric->expectation();
    }

    public static function complexity(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::Ccn2, $limit, $allowEmpty);
    }

    public static function lines(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::Lines, $limit, $allowEmpty);
    }

    public static function parameters(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::Params, $limit, $allowEmpty);
    }

    /**
     * Null when the metric does not apply to the method, as `ccn2` and
     * `lines` do not to abstract and interface methods.
     */
    public function valueFor(MethodMeasurements $method): ?int
    {
        return match ($this->metric) {
            Metric::Ccn2 => $method->ccn2,
            Metric::Lines => $method->lines,
            Metric::Params => $method->params,
        };
    }

    /**
     * Limits are inclusive: a value equal to the ceiling is allowed. A
     * baseline can only ever raise that ceiling, never lower it.
     */
    public function allows(int $value, ?int $accepted = null): bool
    {
        return $value <= max($this->limit, $accepted ?? $this->limit);
    }

    public function metricLabel(): string
    {
        return "{$this->metric->value} v{$this->metric->version()}";
    }

    private static function make(Metric $metric, int $limit, bool $allowEmpty): self
    {
        if ($limit < 0) {
            throw InvalidLimit::negative($metric->label(), $limit);
        }

        return new self($metric, $limit, $allowEmpty);
    }
}
