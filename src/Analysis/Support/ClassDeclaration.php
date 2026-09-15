<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * One class-like declaration `MetricsVisitor` walked, kept with its node so
 * the counts are derived once the whole file is known: a parent declared
 * further down the same file is still a parent.
 *
 * Every count is of what the declaration itself holds. A method or a
 * property reached through `extends` or `use` belongs to the class or the
 * trait that declares it, never to this one.
 */
final readonly class ClassDeclaration
{
    public function __construct(
        public string $symbol,
        private ClassLike $node,
    ) {
    }

    public function line(): int
    {
        return $this->node->getStartLine();
    }

    public function endLine(): int
    {
        return $this->node->getEndLine();
    }

    public function isClass(): bool
    {
        return $this->node instanceof Class_;
    }

    /** The resolved name of the extended class, if the declaration extends one. */
    public function parent(): ?string
    {
        if (! $this->node instanceof Class_ || !$this->node->extends instanceof Name) {
            return null;
        }

        return $this->node->extends->toString();
    }

    public function methods(): int
    {
        return count($this->node->getMethods());
    }

    public function accessors(): int
    {
        return count(array_filter(
            $this->node->getMethods(),
            AccessorMethod::matches(...),
        ));
    }

    /** Promoted constructor parameters included; constants and enum cases excluded. */
    public function properties(): int
    {
        $declared = 0;

        foreach ($this->node->getProperties() as $property) {
            $declared += count($property->props);
        }

        $constructor = $this->node->getMethod('__construct');

        if (! $constructor instanceof ClassMethod) {
            return $declared;
        }

        return $declared + count(array_filter(
            $constructor->params,
            static fn (Param $param): bool => $param->isPromoted(),
        ));
    }

    /** The byte offset of the declaration's closing brace. */
    public function endFilePos(): int
    {
        return FilePosition::of($this->node, 'endFilePos');
    }
}
