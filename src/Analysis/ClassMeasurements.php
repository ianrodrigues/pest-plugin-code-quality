<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

use IanRodrigues\CodeQuality\Analysis\Support\SymbolLocation;
use IanRodrigues\CodeQuality\Metrics\Metric;

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
        /** @var list<Contribution> */
        public array $methodDeclarations = [],
        /** @var list<Contribution> */
        public array $accessorDeclarations = [],
        /** @var list<Contribution> */
        public array $propertyDeclarations = [],
        /** @var list<string> */
        public array $parents = [],
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
     * @return list<Contribution>
     */
    public function contributionsTo(Metric $metric, bool $ignoringAccessors): array
    {
        return match ($metric) {
            Metric::Methods => $ignoringAccessors ? $this->methodDeclarationsExcludingAccessors() : $this->methodDeclarations,
            Metric::Properties => $this->propertyDeclarations,
            Metric::Inheritance => array_map(
                static fn (string $parent): Contribution => new Contribution($parent, null),
                $this->parents,
            ),
            Metric::Ccn2,
            Metric::Lines,
            Metric::Params,
            Metric::ClassLines,
            Metric::ClassName,
            Metric::MethodName,
            Metric::VariableName => [],
        };
    }

    /**
     * A class cannot declare two methods with one name, so the name
     * alone identifies an accessor.
     *
     * @return list<Contribution>
     */
    private function methodDeclarationsExcludingAccessors(): array
    {
        $accessorNames = array_map(
            static fn (Contribution $accessor): string => $accessor->label,
            $this->accessorDeclarations,
        );

        return array_values(array_filter(
            $this->methodDeclarations,
            static fn (Contribution $method): bool => ! in_array($method->label, $accessorNames, true),
        ));
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
