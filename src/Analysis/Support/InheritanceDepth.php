<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis\Support;

use ReflectionClass;
use Throwable;

/**
 * Walks a class up to its root, counting one parent per step. A class
 * from the measured file resolves without loading; everything else
 * falls back to reflection, so an unloadable parent just ends the walk.
 */
final class InheritanceDepth
{
    private function __construct()
    {
    }

    /**
     * @param array<string, string|null> $declaredInFile class symbol => parent symbol
     */
    public static function of(string $symbol, array $declaredInFile): int
    {
        return count(self::chainOf($symbol, $declaredInFile));
    }

    /**
     * @param array<string, string|null> $declaredInFile class symbol => parent symbol
     * @return list<string> parents, nearest first
     */
    public static function chainOf(string $symbol, array $declaredInFile): array
    {
        $chain = [];
        $seen = [$symbol => true];
        $parent = self::parentOf($symbol, $declaredInFile);

        while ($parent !== null && ! isset($seen[$parent])) {
            $chain[] = $parent;
            $seen[$parent] = true;
            $parent = self::parentOf($parent, $declaredInFile);
        }

        return $chain;
    }

    /**
     * @param array<string, string|null> $declaredInFile
     */
    private static function parentOf(string $symbol, array $declaredInFile): ?string
    {
        if (array_key_exists($symbol, $declaredInFile)) {
            return $declaredInFile[$symbol];
        }

        return self::reflectedParentOf($symbol);
    }

    private static function reflectedParentOf(string $symbol): ?string
    {
        try {
            if (! class_exists($symbol)) {
                return null;
            }

            $parent = new ReflectionClass($symbol)->getParentClass();
        } catch (Throwable) {
            // An autoloader that fails on a class this package only
            // counts must not fail the run that counts it.
            return null;
        }

        return $parent === false ? null : $parent->getName();
    }
}
