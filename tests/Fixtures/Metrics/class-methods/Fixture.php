<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods;

trait Timestamps
{
    // a trait method belongs to the trait, never to the class that uses it
    public function touch(): void
    {
        $this->touched = true;
        $this->touchedAt = 1;
    }
}

abstract class Base
{
    // an inherited method is counted where it is declared, never on the child
    public function describe(): string
    {
        return 'base';
    }
}

final class Fixture extends Base
{
    use Timestamps;

    public bool $touched = false;

    public int $touchedAt = 0;

    // methods: a promoted constructor is one method, whatever it promotes
    public function __construct(private string $name, private int $size)
    {
    }

    // accessor: one `return $this->prop;`
    public function name(): string
    {
        return $this->name;
    }

    // accessor: one assignment, then `return $this;`
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    // accessor: one assignment, nothing after it
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    // not an accessor: the body computes a value of its own
    public function summary(): string
    {
        return $this->name.':'.$this->size;
    }
}
