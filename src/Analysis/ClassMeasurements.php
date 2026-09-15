<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use IanRodrigues\CodeQuality\Analysis\Support\SymbolLocation;

/**
 * `inheritance` is `null` for an interface, a trait, or an enum, since
 * none of those extend a class. `accessors` is the share of `methods`
 * that `ignoringAccessors: true` drops, not a metric of its own.
 */
final readonly class ClassMeasurements
{
    public string $symbol;

    public string $path;

    public int $line;

    public int $endLine;

    public function __construct(
        SymbolLocation $location,
        public int $methods,
        public int $accessors,
        public int $properties,
        public ?int $inheritance,
        public int $classLines,
        public int $className,
    ) {
        $this->symbol = $location->symbol;
        $this->path = $location->path;
        $this->line = $location->line;
        $this->endLine = $location->endLine;
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
     * @return array{methods: int, accessors: int, properties: int, inheritance: int|null, classLines: int, className: int}
     */
    public function toArray(): array
    {
        return [
            'methods' => $this->methods,
            'accessors' => $this->accessors,
            'properties' => $this->properties,
            'inheritance' => $this->inheritance,
            'classLines' => $this->classLines,
            'className' => $this->className,
        ];
    }
}
