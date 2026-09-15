<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

/**
 * `inheritance` is `null` for an interface, a trait and an enum, none of
 * which extend a class; every other value applies to every class-like
 * declaration. `accessors` is not a metric of its own: it is the share of
 * `methods` that `toHaveMethodsAtMost(ignoringAccessors: true)` drops.
 */
final readonly class ClassMeasurements
{
    public function __construct(
        public string $symbol,
        public string $path,
        public int $line,
        public int $endLine,
        public int $methods,
        public int $accessors,
        public int $properties,
        public ?int $inheritance,
        public int $classLines,
    ) {
    }

    /**
     * An anonymous class is measured but has no stable, addressable
     * symbol, so it is never a valid target for the `Measurer` contract.
     */
    public function isAnonymous(): bool
    {
        return str_starts_with($this->symbol, MethodMeasurements::ANONYMOUS_SYMBOL_PREFIX);
    }

    public function declaredMethods(bool $ignoringAccessors): int
    {
        return $ignoringAccessors ? $this->methods - $this->accessors : $this->methods;
    }

    /**
     * @return array{methods: int, accessors: int, properties: int, inheritance: int|null, classLines: int}
     */
    public function toArray(): array
    {
        return [
            'methods' => $this->methods,
            'accessors' => $this->accessors,
            'properties' => $this->properties,
            'inheritance' => $this->inheritance,
            'classLines' => $this->classLines,
        ];
    }
}
