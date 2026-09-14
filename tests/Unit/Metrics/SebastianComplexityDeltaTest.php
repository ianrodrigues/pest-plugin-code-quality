<?php

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use SebastianBergmann\Complexity\Calculator;

/**
 * sebastian/complexity's cyclomatic complexity visitor
 * (SebastianBergmann\Complexity\Visitor\CyclomaticComplexityCalculatingVisitor)
 * switches on a fixed list of node classes that does not include the
 * null-coalescing operators (`??`, `??=`) or the nullsafe member access
 * operator (`?->`). Our `ccn2` v1 definition counts all three. For a method
 * whose only counted constructs are those operators, the two values are
 * related by:
 *
 *     sebastian_ccn2 = our_ccn2 - occurrences_of('??', '??=', '?->')
 *
 * This test computes both sides mechanically (our expected `ccn2` values
 * from the pinned `expected.php`, sebastian's value from its own
 * `Calculator`, and the operator occurrences by walking the same AST) so it
 * fails loudly if either fixture or either library's behaviour changes.
 */
function countCoalesceAndNullsafeOperators(ClassMethod $method): int
{
    $stmts = $method->getStmts();

    if ($stmts === null) {
        return 0;
    }

    $visitor = new class () extends NodeVisitorAbstract {
        public int $count = 0;

        public function enterNode(Node $node): null
        {
            if ($node instanceof Coalesce
                || $node instanceof AssignCoalesce
                || $node instanceof NullsafeMethodCall
                || $node instanceof NullsafePropertyFetch
            ) {
                $this->count++;
            }

            return null;
        }
    };

    $traverser = new NodeTraverser();
    $traverser->addVisitor($visitor);
    $traverser->traverse($stmts);

    return $visitor->count;
}

/**
 * @param non-empty-string $fixturePath
 */
function assertSebastianComplexityDelta(string $fixturePath, string $expectedPath): void
{
    /** @var array<string, array{0: int|null, 1: int|null, 2: int}> $expected */
    $expected = require $expectedPath;

    $source = file_get_contents($fixturePath);

    if ($source === false) {
        throw new RuntimeException("Could not read fixture file: {$fixturePath}");
    }

    $ast = (new ParserFactory())->createForNewestSupportedVersion()->parse($source);

    if ($ast === null) {
        throw new RuntimeException("Could not parse fixture file: {$fixturePath}");
    }

    $methodCollector = new class () extends NodeVisitorAbstract {
        /** @var list<ClassMethod> */
        public array $methods = [];

        public function enterNode(Node $node): null
        {
            if ($node instanceof ClassMethod) {
                $this->methods[] = $node;
            }

            return null;
        }
    };

    $traverser = new NodeTraverser();
    $traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver());
    $traverser->addVisitor(new PhpParser\NodeVisitor\ParentConnectingVisitor());
    $traverser->addVisitor($methodCollector);
    $traverser->traverse($ast);

    $methods = $methodCollector->methods;

    $sebastianComplexities = [];
    foreach ((new Calculator())->calculateForSourceFile($fixturePath) as $complexity) {
        $sebastianComplexities[$complexity->name()] = $complexity->cyclomaticComplexity();
    }

    foreach ($methods as $method) {
        $parent = $method->getAttribute('parent');
        $className = $parent instanceof ClassLike && $parent->namespacedName !== null
            ? $parent->namespacedName->toString()
            : null;

        if ($className === null) {
            throw new RuntimeException('Could not resolve the class name for a fixture method.');
        }

        $symbol = $className . '::' . $method->name->toString();

        expect($expected)->toHaveKey($symbol);
        expect($sebastianComplexities)->toHaveKey($symbol);

        $ourCcn2 = $expected[$symbol][0];
        $occurrences = countCoalesceAndNullsafeOperators($method);

        expect($sebastianComplexities[$symbol])->toBe($ourCcn2 - $occurrences);
    }
}

it('accounts for the ??/??= delta between sebastian/complexity and our ccn2 on the null-coalescing fixture', function (): void {
    assertSebastianComplexityDelta(
        __DIR__ . '/../../Fixtures/Metrics/null-coalescing/Fixture.php',
        __DIR__ . '/../../Fixtures/Metrics/null-coalescing/expected.php',
    );
});

it('accounts for the ?-> delta between sebastian/complexity and our ccn2 on the nullsafe fixture', function (): void {
    assertSebastianComplexityDelta(
        __DIR__ . '/../../Fixtures/Metrics/nullsafe/Fixture.php',
        __DIR__ . '/../../Fixtures/Metrics/nullsafe/expected.php',
    );
});
