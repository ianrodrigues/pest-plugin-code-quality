<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\PHPStan;

use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\PendingArchExpectation;
use Pest\Expectation;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

/**
 * The expectations are registered at runtime through `expect()->extend()`,
 * so no source declares them where static analysis can see them. This puts
 * them back on the three types an architecture chain flows through.
 */
final class MethodLimitsExtension implements MethodsClassReflectionExtension
{
    private const array METHODS = [
        'toHaveMethodComplexityAtMost',
        'toHaveMethodLinesAtMost',
        'toHaveMethodParametersAtMost',
        'toHaveMethodsAtMost',
        'toHavePropertiesAtMost',
        'toHaveInheritanceDepthAtMost',
        'toHaveClassLinesAtMost',
    ];

    private const array TYPES = [
        Expectation::class,
        PendingArchExpectation::class,
        ArchExpectation::class,
    ];

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! in_array($methodName, self::METHODS, true)) {
            return false;
        }
        return array_any(self::TYPES, fn (string $type): bool => $classReflection->getName() === $type || $classReflection->isSubclassOf($type));
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new MethodLimitReflection($classReflection, $methodName);
    }
}
