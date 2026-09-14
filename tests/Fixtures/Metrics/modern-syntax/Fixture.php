<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\ModernSyntax;

enum Status
{
    case Active;
    case Inactive;

    // ccn2: 1 + arm + arm = 3 (no default arm present)
    public function label(): string
    {
        return match ($this) {
            self::Active => 'active',
            self::Inactive => 'inactive',
        };
    }
}

final readonly class Money
{
    // ccn2: 1 (empty body); params: 2 (both promoted)
    public function __construct(
        public int $amount,
        public string $currency,
    ) {
    }
}

final class Account
{
    // asymmetric visibility: readable from anywhere, writable only from
    // within the class. Not a method, so it contributes no ccn2/lines entry.
    public private(set) int $id = 0;

    private int $balanceInCents = 0;

    // computed (virtual) property backed by a hook pair. Not a method, so it
    // contributes no ccn2/lines entry either.
    public float $balanceInDollars {
        get => $this->balanceInCents / 100;
        set(float $value) {
            $this->balanceInCents = (int) round($value * 100);
        }
    }

    // ccn2: 1 (empty body; the `new Money(...)` default value is evaluated at
    // call time, not a counted construct); params: 1
    public function __construct(
        private Money $money = new Money(0, 'USD'),
    ) {
    }

    // ccn2: 1 (a first-class callable reference is not a counted construct)
    public function upper(): callable
    {
        return strtoupper(...);
    }

    // ccn2: 1 (the pipe operator chains calls but is not itself a counted
    // construct)
    public function normalize(string $input): string
    {
        return $input |> trim(...) |> strtoupper(...);
    }
}
