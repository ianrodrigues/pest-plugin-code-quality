<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Analysis\ClassMeasurements;
use IanRodrigues\CodeQuality\Analysis\Contribution;
use IanRodrigues\CodeQuality\Analysis\Support\SymbolLocation;
use IanRodrigues\CodeQuality\Policies\Policy;

/**
 * @param list<Contribution> $contributions
 * @return list<array{label: string, line: int|null}>
 */
function contributions_as_arrays(array $contributions): array
{
    return array_map(static fn (Contribution $c): array => $c->toArray(), $contributions);
}

it('excludes the accessors from a methods policy contributions when ignoringAccessors is true', function (): void {
    $class = new ClassMeasurements(
        location: new SymbolLocation('App\Wide', 'app/Wide.php', 7, 30),
        methods: 4,
        accessors: 2,
        properties: 0,
        inheritance: 0,
        classLines: 20,
        className: 4,
        methodDeclarations: [
            new Contribution('__construct', 13),
            new Contribution('id', 17),
            new Contribution('setSize', 22),
            new Contribution('describe', 29),
        ],
        accessorDeclarations: [
            new Contribution('id', 17),
            new Contribution('setSize', 22),
        ],
    );

    $policy = Policy::methods(limit: 1, ignoringAccessors: true);

    expect(contributions_as_arrays($policy->contributionsFor($class)))->toBe([
        ['label' => '__construct', 'line' => 13],
        ['label' => 'describe', 'line' => 29],
    ]);
});

it('keeps every declared method when ignoringAccessors is false', function (): void {
    $class = new ClassMeasurements(
        location: new SymbolLocation('App\Wide', 'app/Wide.php', 7, 30),
        methods: 4,
        accessors: 2,
        properties: 0,
        inheritance: 0,
        classLines: 20,
        className: 4,
        methodDeclarations: [
            new Contribution('__construct', 13),
            new Contribution('id', 17),
            new Contribution('setSize', 22),
            new Contribution('describe', 29),
        ],
        accessorDeclarations: [
            new Contribution('id', 17),
            new Contribution('setSize', 22),
        ],
    );

    $policy = Policy::methods(limit: 1);

    expect(contributions_as_arrays($policy->contributionsFor($class)))->toBe([
        ['label' => '__construct', 'line' => 13],
        ['label' => 'id', 'line' => 17],
        ['label' => 'setSize', 'line' => 22],
        ['label' => 'describe', 'line' => 29],
    ]);
});
