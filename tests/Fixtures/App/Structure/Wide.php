<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure;

final class Wide
{
    public string $name = '';

    public int $size = 0;

    public function __construct(private readonly string $id)
    {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function describe(): string
    {
        return $this->id.'/'.$this->size;
    }
}
