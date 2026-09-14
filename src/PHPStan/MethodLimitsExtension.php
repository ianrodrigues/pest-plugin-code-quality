<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\PHPStan;

use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\PendingArchExpectation;
use Pest\Expectation;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

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
    ];

    private const array TYPES = [
        Expectation::class,
        PendingArchExpectation::class,
        ArchExpectation::class,
    ];

    /**
     * A Pest expectation with the same `(int): ArchExpectation` shape.
     */
    private const string SIGNATURE_SOURCE = 'toHaveLineCountLessThan';

    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! in_array($methodName, self::METHODS, true)) {
            return false;
        }

        foreach (self::TYPES as $type) {
            if ($classReflection->getName() === $type || $classReflection->isSubclassOf($type)) {
                return true;
            }
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $signature = $this->reflectionProvider
            ->getClass(Expectation::class)
            ->getNativeMethod(self::SIGNATURE_SOURCE);

        return new MethodLimitReflection($classReflection, $methodName, $signature);
    }
}
