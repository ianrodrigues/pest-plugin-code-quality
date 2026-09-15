<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;

/**
 * Recognises the two method bodies the `methods` metric can be asked to
 * ignore: a getter that only returns one of its own properties, and a
 * setter that only assigns one, with or without a fluent `return $this;`.
 */
final class AccessorMethod
{
    private function __construct()
    {
    }

    public static function matches(ClassMethod $node): bool
    {
        $stmts = $node->stmts;

        if ($stmts === null || $stmts === []) {
            return false;
        }

        if (count($stmts) === 1) {
            return self::isGetter($stmts[0]) || self::isSetter($stmts[0]);
        }

        return count($stmts) === 2 && self::isSetter($stmts[0]) && self::returnsThis($stmts[1]);
    }

    private static function isGetter(Stmt $stmt): bool
    {
        return $stmt instanceof Return_ && self::isOwnProperty($stmt->expr);
    }

    private static function isSetter(Stmt $stmt): bool
    {
        return $stmt instanceof Expression
            && $stmt->expr instanceof Assign
            && self::isOwnProperty($stmt->expr->var);
    }

    private static function returnsThis(Stmt $stmt): bool
    {
        return $stmt instanceof Return_ && self::isThis($stmt->expr);
    }

    private static function isOwnProperty(?Expr $expr): bool
    {
        return $expr instanceof PropertyFetch
            && $expr->name instanceof Identifier
            && self::isThis($expr->var);
    }

    private static function isThis(?Expr $expr): bool
    {
        return $expr instanceof Variable && $expr->name === 'this';
    }
}
