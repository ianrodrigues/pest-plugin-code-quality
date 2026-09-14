<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Analysis\Support;

use LogicException;
use PhpParser\Node;
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
use PhpParser\Node\MatchArm;
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
use Rdgs\PestCodeQuality\Analysis\MethodMeasurements;

/**
 * Computes `ccn2`, `lines` and `params` for every method in a resolved AST
 * in a single traversal, per the "Metric definitions" README section.
 *
 * Closures and arrow functions do not get their own `ccn2` context: any
 * counted construct inside one is attributed to the innermost enclosing
 * method. Anonymous classes are entered without starting a new `ccn2`
 * context of their own (so their declaration does not add to the enclosing
 * method's `ccn2`), but each of their methods pushes its own context, same
 * as any other method.
 */
final class MetricsVisitor extends NodeVisitorAbstract
{
    /** @var list<string> */
    private array $classLikeStack = [];

    /** @var list<MethodAccumulator> */
    private array $methodStack = [];

    /** @var list<MethodMeasurements> */
    private array $methods = [];

    public function __construct(
        private readonly string $path,
        private readonly SourceTokens $tokens,
    ) {
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof ClassLike) {
            $this->classLikeStack[] = $this->classLikeSymbol($node);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->methodStack[] = $this->startMethod($node);

            return null;
        }

        if ($this->methodStack === []) {
            return null;
        }

        $delta = $this->ccn2Delta($node);

        if ($delta > 0) {
            $current = $this->methodStack[array_key_last($this->methodStack)];

            if ($current->ccn2 !== null) {
                $current->ccn2 += $delta;
            }
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

    private function classLikeSymbol(ClassLike $node): string
    {
        if ($node instanceof Class_ && $node->name === null) {
            return MethodMeasurements::ANONYMOUS_SYMBOL_PREFIX . $node->getStartLine();
        }

        if ($node->namespacedName === null) {
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
            ? $this->tokens->countBodyLines($this->filePos($node, 'endFilePos'))
            : null;

        return new MethodMeasurements(
            symbol: $accumulator->symbol,
            path: $this->path,
            line: $accumulator->line,
            endLine: $accumulator->endLine,
            ccn2: $accumulator->ccn2,
            lines: $lines,
            params: $accumulator->params,
        );
    }

    private function filePos(Node $node, string $attribute): int
    {
        $value = $node->getAttribute($attribute);

        if (! is_int($value)) {
            throw new LogicException("Node is missing its \"{$attribute}\" attribute.");
        }

        return $value;
    }

    private function ccn2Delta(Node $node): int
    {
        return match (true) {
            $node instanceof If_,
            $node instanceof ElseIf_,
            $node instanceof For_,
            $node instanceof Foreach_,
            $node instanceof While_,
            $node instanceof Do_,
            $node instanceof Catch_,
            $node instanceof Ternary,
            $node instanceof BooleanAnd,
            $node instanceof BooleanOr,
            $node instanceof LogicalAnd,
            $node instanceof LogicalOr,
            $node instanceof LogicalXor,
            $node instanceof Coalesce,
            $node instanceof AssignCoalesce,
            $node instanceof NullsafePropertyFetch,
            $node instanceof NullsafeMethodCall => 1,
            $node instanceof Case_ => $node->cond !== null ? 1 : 0,
            $node instanceof MatchArm => $node->conds !== null ? 1 : 0,
            default => 0,
        };
    }
}
