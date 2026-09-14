<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Metrics;

/**
 * A metric this package measures: `ccn2`, `lines` or `params`.
 *
 * `version()` is the metric's definition version, stamped on every
 * measurement and stored in every baseline entry. Widening or narrowing
 * what a metric counts bumps the `match` in `version()` rather than
 * silently reinterpreting existing baselines.
 */
enum Metric: string
{
    case Ccn2 = 'ccn2';
    case Lines = 'lines';
    case Params = 'params';

    public function version(): int
    {
        return match ($this) {
            self::Ccn2 => 1,
            self::Lines => 1,
            self::Params => 1,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Ccn2 => 'Method complexity',
            self::Lines => 'Method lines',
            self::Params => 'Method parameters',
        };
    }

    public function expectation(): string
    {
        return match ($this) {
            self::Ccn2 => 'toHaveMethodComplexityAtMost',
            self::Lines => 'toHaveMethodLinesAtMost',
            self::Params => 'toHaveMethodParametersAtMost',
        };
    }

    public function identifier(): string
    {
        return "{$this->value}@{$this->version()}";
    }
}
