<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\PHPStan;

use Pest\Arch\Contracts\ArchExpectation;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Built by hand rather than borrowed from an existing Pest expectation:
 * none of Pest's own `(int): ArchExpectation` expectations also take a
 * second, optional `bool`, so no signature has the right shape to copy.
 */
final class MethodLimitReflection implements MethodReflection
{
    public function __construct(
        private readonly ClassReflection $declaringClass,
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    /**
     * @return list<ParametersAcceptor>
     */
    public function getVariants(): array
    {
        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                null,
                [
                    new MethodLimitParameter('max', new IntegerType(), false),
                    new MethodLimitParameter('allowEmpty', new BooleanType(), true, new ConstantBooleanType(false)),
                ],
                false,
                new ObjectType(ArchExpectation::class),
            ),
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return null;
    }
}
