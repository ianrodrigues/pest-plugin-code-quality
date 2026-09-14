<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Analysis\AstMeasurer;

/**
 * @return array<string, array{int|null, int|null, int}>
 */
function measureSource(string $source): array
{
    $path = tempnam(sys_get_temp_dir(), 'pest-quality-symbols-') . '.php';

    file_put_contents($path, $source);

    try {
        return new AstMeasurer()->measure($path);
    } finally {
        unlink($path);
    }
}

it('attributes a trait method to the declaring trait, never to a using class', function (): void {
    $result = measureSource(<<<'PHP'
        <?php

        namespace Symbols;

        trait Greets
        {
            public function greet(): string
            {
                return 'hi';
            }
        }

        final class Person
        {
            use Greets;
        }
        PHP);

    expect($result)->toHaveKey('Symbols\\Greets::greet')->not->toHaveKey('Symbols\\Person::greet');
});

it('resolves an enum method symbol against the enum, not a case', function (): void {
    $result = measureSource(<<<'PHP'
        <?php

        namespace Symbols;

        enum Suit
        {
            case Hearts;

            public function label(): string
            {
                return 'hearts';
            }
        }
        PHP);

    expect($result)->toHaveKey('Symbols\\Suit::label');
});

it('resolves a static method symbol the same way as an instance method', function (): void {
    $result = measureSource(<<<'PHP'
        <?php

        namespace Symbols;

        final class Factory
        {
            public static function make(): self
            {
                return new self();
            }
        }
        PHP);

    expect($result)->toHaveKey('Symbols\\Factory::make');
});

it('resolves the constructor symbol and counts promoted parameters', function (): void {
    $result = measureSource(<<<'PHP'
        <?php

        namespace Symbols;

        final class Point
        {
            public function __construct(
                private readonly int $x,
                private readonly int $y,
            ) {
            }
        }
        PHP);

    expect($result)->toHaveKey('Symbols\\Point::__construct')
        ->and($result['Symbols\\Point::__construct'])->toBe([1, 0, 2]);
});

it('resolves magic method symbols', function (): void {
    $result = measureSource(<<<'PHP'
        <?php

        namespace Symbols;

        final class Wrapper
        {
            public function __get(string $name): mixed
            {
                return null;
            }

            public function __call(string $name, array $arguments): mixed
            {
                return null;
            }
        }
        PHP);

    expect($result)->toHaveKeys(['Symbols\\Wrapper::__get', 'Symbols\\Wrapper::__call']);
});
