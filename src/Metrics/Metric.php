<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Metrics;

/**
 * A metric this package measures, per method (`ccn2`, `lines`, `params`, `methodName`, `variableName`)
 * or class-like declaration (`methods`, `properties`, `inheritance`, `classLines`, `className`);
 * `version()` bumps to mark baselines stale instead of reinterpreting them silently.
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
    case ClassName = 'className';
    case MethodName = 'methodName';
    case VariableName = 'variableName';

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
            self::ClassName => 1,
            self::MethodName => 1,
            self::VariableName => 1,
        };
    }

    public function scope(): Scope
    {
        return match ($this) {
            self::Ccn2, self::Lines, self::Params, self::MethodName, self::VariableName => Scope::Method,
            self::Methods, self::Properties, self::Inheritance, self::ClassLines, self::ClassName => Scope::ClassLike,
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
            self::ClassName => 'Class name length',
            self::MethodName => 'Method name length',
            self::VariableName => 'Variable name length',
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
            self::ClassName => 'toHaveClassNamesAtMost',
            self::MethodName => 'toHaveMethodNamesAtMost',
            self::VariableName => 'toHaveVariableNamesAtMost',
        };
    }

    public function identifier(): string
    {
        return "{$this->value}@{$this->version()}";
    }
}
