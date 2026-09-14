<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Policies;

use Pest\Arch\Factories\LayerFactory;
use Pest\Arch\Options\LayerOptions;
use Pest\Arch\Repositories\ObjectsRepository;
use Pest\Arch\ValueObjects\Targets as ArchTargets;
use Pest\Expectation;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Resolution goes through the architecture plugin's own layer factory, so
 * `ignoring()`, `test()->arch()->ignore(...)` and the `classes()`-style
 * filters behave exactly as they do for built-in expectations.
 */
final readonly class Targets
{
    private function __construct(
        private ArchTargets $targets,
        private LayerFactory $factory,
    ) {
    }

    /**
     * @param Expectation<array<int, string>|string> $expectation
     */
    public static function fromExpectation(Expectation $expectation): self
    {
        return new self(
            ArchTargets::fromExpectation($expectation),
            new LayerFactory(ObjectsRepository::getInstance()),
        );
    }

    /**
     * @param array<int, string> $values
     */
    public static function fromValues(array $values): self
    {
        return new self(
            new ArchTargets($values),
            new LayerFactory(ObjectsRepository::getInstance()),
        );
    }

    /**
     * @return list<ObjectDescription>
     */
    public function resolve(LayerOptions $options): array
    {
        $objects = [];
        $seen = [];

        foreach ($this->values() as $target) {
            foreach ($this->resolveTarget($options, $target) as $object) {
                if (isset($seen[$object->name])) {
                    continue;
                }

                $seen[$object->name] = true;
                $objects[] = $object;
            }
        }

        return $objects;
    }

    /**
     * The raw target strings this instance was built from, before the
     * architecture layer resolves each into objects.
     *
     * @return list<string>
     */
    public function values(): array
    {
        return array_values($this->targets->value);
    }

    /**
     * Resolves one raw target string on its own, so completeness
     * accounting can be built per target instead of across the flattened,
     * deduplicated set `resolve()` returns.
     *
     * @return list<ObjectDescription>
     */
    public function resolveTarget(LayerOptions $options, string $target): array
    {
        return iterator_to_array($this->factory->make($options, $target), false);
    }
}
