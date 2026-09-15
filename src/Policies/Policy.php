<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Policies;

use IanRodrigues\CodeQuality\Analysis\ClassMeasurements;
use IanRodrigues\CodeQuality\Analysis\Contribution;
use IanRodrigues\CodeQuality\Analysis\FileMeasurements;
use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Exceptions\InvalidLimit;
use IanRodrigues\CodeQuality\Metrics\Metric;
use IanRodrigues\CodeQuality\Metrics\Scope;

final readonly class Policy
{
    public string $description;

    public string $expectation;

    private function __construct(
        public Metric $metric,
        public int $limit,
        public bool $allowEmpty = false,
        public bool $ignoringAccessors = false,
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

    public static function methods(int $limit, bool $ignoringAccessors = false, bool $allowEmpty = false): self
    {
        return self::make(Metric::Methods, $limit, $allowEmpty, $ignoringAccessors);
    }

    public static function properties(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::Properties, $limit, $allowEmpty);
    }

    public static function inheritance(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::Inheritance, $limit, $allowEmpty);
    }

    public static function classLines(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::ClassLines, $limit, $allowEmpty);
    }

    public static function classNames(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::ClassName, $limit, $allowEmpty);
    }

    public static function methodNames(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::MethodName, $limit, $allowEmpty);
    }

    public static function variableNames(int $limit, bool $allowEmpty = false): self
    {
        return self::make(Metric::VariableName, $limit, $allowEmpty);
    }

    /**
     * The symbols this policy compares against its limit: the methods of
     * the file for a method-scoped metric, its class-like declarations for
     * a class-scoped one.
     *
     * @return list<ClassMeasurements|MethodMeasurements>
     */
    public function symbolsIn(FileMeasurements $file): array
    {
        return match ($this->metric->scope()) {
            Scope::Method => $file->methods(),
            Scope::ClassLike => $file->classes(),
        };
    }

    /**
     * Null when the metric does not apply: `ccn2`/`lines` to an abstract or
     * interface method, `inheritance` to an interface, and any
     * method-scoped metric to a class.
     */
    public function valueFor(ClassMeasurements|MethodMeasurements $symbol): ?int
    {
        return $symbol instanceof ClassMeasurements
            ? $this->classValue($symbol)
            : $this->methodValue($symbol);
    }

    /**
     * @return list<Contribution>
     */
    public function contributionsFor(ClassMeasurements|MethodMeasurements $symbol): array
    {
        return $symbol instanceof ClassMeasurements
            ? $symbol->contributionsTo($this->metric, $this->ignoringAccessors)
            : $symbol->contributionsTo($this->metric);
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

    private function methodValue(MethodMeasurements $method): ?int
    {
        return match ($this->metric) {
            Metric::Ccn2 => $method->ccn2,
            Metric::Lines => $method->lines,
            Metric::Params => $method->params,
            Metric::MethodName => $method->isMagicMethod() ? null : $method->methodName,
            Metric::VariableName => $method->variableName,
            Metric::Methods, Metric::Properties, Metric::Inheritance, Metric::ClassLines, Metric::ClassName => null,
        };
    }

    private function classValue(ClassMeasurements $class): ?int
    {
        return match ($this->metric) {
            Metric::Ccn2, Metric::Lines, Metric::Params, Metric::MethodName, Metric::VariableName => null,
            Metric::Methods => $class->declaredMethods($this->ignoringAccessors),
            Metric::Properties => $class->properties,
            Metric::Inheritance => $class->inheritance,
            Metric::ClassLines => $class->classLines,
            Metric::ClassName => $class->className,
        };
    }

    private static function make(Metric $metric, int $limit, bool $allowEmpty, bool $ignoringAccessors = false): self
    {
        if ($limit < 0) {
            throw InvalidLimit::negative($metric->label(), $limit);
        }

        return new self($metric, $limit, $allowEmpty, $ignoringAccessors);
    }
}
