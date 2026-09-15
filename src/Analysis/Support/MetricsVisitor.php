<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use IanRodrigues\CodeQuality\Analysis\ClassMeasurements;
use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeVisitorAbstract;

/**
 * A construct or declared variable inside a closure, arrow function, or anonymous
 * class body counts toward the innermost enclosing method; anonymous-class methods
 * start their own context, like any method.
 */
final class MetricsVisitor extends NodeVisitorAbstract
{
    /** @var list<string> */
    private array $classLikeStack = [];

    /** @var list<MethodAccumulator> */
    private array $methodStack = [];

    /** @var list<MethodMeasurements> */
    private array $methods = [];

    /** @var list<ClassDeclaration> */
    private array $classes = [];

    public function __construct(
        private readonly string $path,
        private readonly SourceTokens $tokens,
    ) {
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof ClassLike) {
            $symbol = $this->classLikeSymbol($node);

            $this->classLikeStack[] = $symbol;
            $this->classes[] = new ClassDeclaration($symbol, $node);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->methodStack[] = $this->startMethod($node);

            return null;
        }

        if ($this->methodStack === []) {
            return null;
        }

        $current = $this->methodStack[array_key_last($this->methodStack)];

        foreach (DeclaredVariableNames::at($node) as $name) {
            $current->recordVariable($name);
        }

        $label = $this->ccn2Label($node);

        if ($label !== null) {
            $current->recordConstruct($label, $node->getStartLine());
        }

        return null;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof ClassLike) {
            array_pop($this->classLikeStack);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->methods[] = $this->finishMethod($node);
        }

        return null;
    }

    /**
     * @return list<MethodMeasurements>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * Resolved once the traversal is over, so that a parent declared
     * further down the same file still counts towards `inheritance`.
     *
     * @return list<ClassMeasurements>
     */
    public function classes(): array
    {
        $parents = [];

        foreach ($this->classes as $class) {
            $parents[$class->symbol] = $class->parent();
        }

        return array_map(
            fn (ClassDeclaration $class): ClassMeasurements => $this->finishClass($class, $parents),
            $this->classes,
        );
    }

    private function classLikeSymbol(ClassLike $node): string
    {
        if ($node instanceof Class_ && !$node->name instanceof Identifier) {
            return MethodMeasurements::ANONYMOUS_SYMBOL_PREFIX . $node->getStartLine();
        }

        if (!$node->namespacedName instanceof Name) {
            throw new LogicException(
                'Encountered a class-like declaration with no resolved name; run NameResolver first.',
            );
        }

        return $node->namespacedName->toString();
    }

    private function startMethod(ClassMethod $node): MethodAccumulator
    {
        if ($this->classLikeStack === []) {
            throw new LogicException('Encountered a method declared outside of any class-like context.');
        }

        $symbol = $this->classLikeStack[array_key_last($this->classLikeStack)] . '::' . $node->name->toString();

        return new MethodAccumulator(
            symbol: $symbol,
            line: $node->getStartLine(),
            endLine: $node->getEndLine(),
            params: count($node->params),
            hasBody: $node->stmts !== null,
        );
    }

    private function finishMethod(ClassMethod $node): MethodMeasurements
    {
        $accumulator = array_pop($this->methodStack)
            ?? throw new LogicException('Left a method that was never entered.');

        $lines = $accumulator->hasBody
            ? $this->tokens->countBodyLines(FilePosition::of($node, 'endFilePos'))
            : null;

        return new MethodMeasurements(
            location: new SymbolLocation($accumulator->symbol, $this->path, $accumulator->line, $accumulator->endLine),
            ccn2: $accumulator->ccn2,
            lines: $lines,
            params: $accumulator->params,
            methodName: mb_strlen($node->name->toString()),
            variableName: $accumulator->variableName,
            longestVariableIdentifier: $accumulator->longestVariableIdentifier,
            ccn2Contributions: $accumulator->ccn2Contributions,
        );
    }

    /**
     * @param array<string, string|null> $parents
     */
    private function finishClass(ClassDeclaration $class, array $parents): ClassMeasurements
    {
        return new ClassMeasurements(
            location: new SymbolLocation($class->symbol, $this->path, $class->line(), $class->endLine()),
            methods: $class->methods(),
            accessors: $class->accessors(),
            properties: $class->properties(),
            inheritance: $class->isClass() ? InheritanceDepth::of($class->symbol, $parents) : null,
            classLines: $this->tokens->countBodyLines($class->endFilePos()),
            className: mb_strlen($class->shortName()),
            methodDeclarations: $class->methodDeclarations(),
            accessorDeclarations: $class->accessorDeclarations(),
            propertyDeclarations: $class->propertyDeclarations(),
            parents: $class->isClass() ? InheritanceDepth::chainOf($class->symbol, $parents) : [],
        );
    }

    private function ccn2Label(Node $node): ?string
    {
        return $this->statementLabel($node) ?? $this->operatorLabel($node) ?? $this->conditionalLabel($node);
    }

    private function statementLabel(Node $node): ?string
    {
        return match (true) {
            $node instanceof If_ => 'if',
            $node instanceof ElseIf_ => 'elseif',
            $node instanceof For_ => 'for',
            $node instanceof Foreach_ => 'foreach',
            $node instanceof While_ => 'while',
            $node instanceof Do_ => 'do',
            $node instanceof Catch_ => 'catch',
            default => null,
        };
    }

    private function operatorLabel(Node $node): ?string
    {
        return match (true) {
            $node instanceof BooleanAnd => '&&',
            $node instanceof BooleanOr => '||',
            $node instanceof LogicalAnd => 'and',
            $node instanceof LogicalOr => 'or',
            $node instanceof LogicalXor => 'xor',
            $node instanceof Coalesce => '??',
            $node instanceof AssignCoalesce => '??=',
            $node instanceof NullsafePropertyFetch,
            $node instanceof NullsafeMethodCall => '?->',
            default => null,
        };
    }

    /** A `default` case or arm is not a branch; a `Ternary` without an `if` branch is the short form. */
    private function conditionalLabel(Node $node): ?string
    {
        if ($node instanceof Ternary) {
            return $node->if instanceof Expr ? '? :' : '?:';
        }

        if ($node instanceof Case_) {
            return $node->cond instanceof Expr ? 'case' : null;
        }

        if ($node instanceof MatchArm) {
            return $node->conds !== null ? 'match arm' : null;
        }

        return null;
    }
}
