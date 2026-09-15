<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use IanRodrigues\CodeQuality\Analysis\Contribution;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Kept with its node so counts are derived once the whole file is
 * known: a parent declared further down is still a parent. Every count
 * is of what the declaration itself holds, not members reached via `extends`/`use`.
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

    /** The declaration's own name, without its namespace. */
    public function shortName(): string
    {
        $separator = strrpos($this->symbol, '\\');

        return $separator === false ? $this->symbol : substr($this->symbol, $separator + 1);
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
        return count($this->methodDeclarations());
    }

    public function accessors(): int
    {
        return count($this->accessorDeclarations());
    }

    /** Promoted constructor parameters included; constants and enum cases excluded. */
    public function properties(): int
    {
        return count($this->propertyDeclarations());
    }

    /**
     * @return list<Contribution>
     */
    public function methodDeclarations(): array
    {
        return array_map(
            static fn (ClassMethod $method): Contribution => new Contribution($method->name->toString(), $method->getStartLine()),
            $this->node->getMethods(),
        );
    }

    /**
     * @return list<Contribution>
     */
    public function accessorDeclarations(): array
    {
        return array_map(
            static fn (ClassMethod $method): Contribution => new Contribution($method->name->toString(), $method->getStartLine()),
            array_values(array_filter(
                $this->node->getMethods(),
                AccessorMethod::matches(...),
            )),
        );
    }

    /**
     * @return list<Contribution>
     */
    public function propertyDeclarations(): array
    {
        $declarations = [];

        foreach ($this->node->getProperties() as $property) {
            foreach ($property->props as $item) {
                $declarations[] = new Contribution($item->name->toString(), $item->getStartLine());
            }
        }

        $constructor = $this->node->getMethod('__construct');

        if (! $constructor instanceof ClassMethod) {
            return $declarations;
        }

        foreach ($constructor->params as $param) {
            if ($param->isPromoted()) {
                $declarations[] = new Contribution($this->promotedParamName($param), $param->getStartLine());
            }
        }

        return $declarations;
    }

    /** The parameter variable is always a plain, named variable in valid PHP. */
    private function promotedParamName(Param $param): string
    {
        return $param->var instanceof Variable && is_string($param->var->name) ? $param->var->name : '';
    }

    /** The byte offset of the declaration's closing brace. */
    public function endFilePos(): int
    {
        return FilePosition::of($this->node, 'endFilePos');
    }
}
