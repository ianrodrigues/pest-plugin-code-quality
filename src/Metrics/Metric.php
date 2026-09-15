<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Metrics;

/**
 * A metric this package measures, per method (`ccn2`, `lines`, `params`)
 * or class-like declaration (`methods`, `properties`, `inheritance`,
 * `classLines`); `version()` bumps to mark baselines stale instead of reinterpreting them silently.
 */
enum Metric: string
{
    case Ccn2 = 'ccn2';
    case Lines = 'lines';
    case Params = 'params';
    case Methods = 'methods';
    case Properties = 'properties';
    case Inheritance = 'inheritance';
    case ClassLines = 'classLines';

    public function version(): int
    {
        return match ($this) {
            self::Ccn2 => 1,
            self::Lines => 1,
            self::Params => 1,
            self::Methods => 1,
            self::Properties => 1,
            self::Inheritance => 1,
            self::ClassLines => 1,
        };
    }

    public function scope(): Scope
    {
        return match ($this) {
            self::Ccn2, self::Lines, self::Params => Scope::Method,
            self::Methods, self::Properties, self::Inheritance, self::ClassLines => Scope::ClassLike,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Ccn2 => 'Method complexity',
            self::Lines => 'Method lines',
            self::Params => 'Method parameters',
            self::Methods => 'Class methods',
            self::Properties => 'Class properties',
            self::Inheritance => 'Inheritance depth',
            self::ClassLines => 'Class lines',
        };
    }

    public function expectation(): string
    {
        return match ($this) {
            self::Ccn2 => 'toHaveMethodComplexityAtMost',
            self::Lines => 'toHaveMethodLinesAtMost',
            self::Params => 'toHaveMethodParametersAtMost',
            self::Methods => 'toHaveMethodsAtMost',
            self::Properties => 'toHavePropertiesAtMost',
            self::Inheritance => 'toHaveInheritanceDepthAtMost',
            self::ClassLines => 'toHaveClassLinesAtMost',
        };
    }

    public function identifier(): string
    {
        return "{$this->value}@{$this->version()}";
    }
}
