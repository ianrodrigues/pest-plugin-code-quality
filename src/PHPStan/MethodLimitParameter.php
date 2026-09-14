<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\PHPStan;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

/**
 * `PHPStan\Reflection\Php\DummyParameter` would do the same job, but its
 * constructor carries no backward compatibility promise; this implements
 * the `@api` `ParameterReflection` interface directly instead.
 */
final readonly class MethodLimitParameter implements ParameterReflection
{
    public function __construct(
        private string $name,
        private Type $type,
        private bool $optional,
        private ?Type $defaultValue = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function passedByReference(): PassedByReference
    {
        return PassedByReference::createNo();
    }

    public function isVariadic(): bool
    {
        return false;
    }

    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
}
