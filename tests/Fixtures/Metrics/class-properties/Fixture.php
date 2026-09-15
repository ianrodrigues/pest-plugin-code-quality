<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassProperties;

trait HasLabel
{
    // a trait property belongs to the trait, never to the class that uses it
    public string $label = '';
}

class Base
{
    // an inherited property is counted where it is declared, never on the child
    protected int $id = 0;
}

final class Fixture extends Base
{
    use HasLabel;

    // a constant is not a property
    public const string KIND = 'fixture';

    // one declaration, two properties
    public ?string $first = null, $second = null;

    public static array $registry = [];

    public function __construct(
        private readonly int $size,
        public string $name = '',
        int $plain = 0,
    ) {
    }
}

enum Kind: string
{
    case Small = 'small';

    case Large = 'large';

    // an enum case is not a property
    public function label(): string
    {
        return $this->value;
    }
}
