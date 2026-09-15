<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use PhpParser\Node;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Foreach_;

/**
 * The variable identifiers `MetricsVisitor` counts towards `variableName`,
 * read at each declaration site the README's "Metric definitions" section
 * lists: a parameter (including a promoted one, and one on a closure or
 * arrow function), a `use` variable on a closure, a local assignment, a
 * `foreach` key or value, and a `catch` variable. `$this` and a superglobal
 * are never a declaration and never counted.
 */
final class DeclaredVariableNames
{
    private const array SUPERGLOBALS = [
        'GLOBALS', '_SERVER', '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST', '_ENV',
    ];

    private function __construct()
    {
    }

    /**
     * @return list<string>
     */
    public static function at(Node $node): array
    {
        return match (true) {
            $node instanceof Param => self::fromVariable($node->var),
            $node instanceof ClosureUse => self::fromVariable($node->var),
            $node instanceof Assign, $node instanceof AssignRef => self::fromVariable($node->var),
            $node instanceof Foreach_ => [
                ...self::fromVariable($node->keyVar),
                ...self::fromVariable($node->valueVar),
            ],
            $node instanceof Catch_ => self::fromVariable($node->var),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private static function fromVariable(?Node $node): array
    {
        if (! $node instanceof Variable || ! is_string($node->name)) {
            return [];
        }

        if ($node->name === 'this' || in_array($node->name, self::SUPERGLOBALS, true)) {
            return [];
        }

        return [$node->name];
    }
}
