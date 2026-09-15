<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Metrics\Metric;
use IanRodrigues\CodeQuality\Metrics\Scope;

it('reports every case exactly once, so a new one forces a new dataset row', function (): void {
    expect(Metric::cases())->toHaveCount(7);
});

it('exposes version, label, expectation and identifier per case', function (
    Metric $metric,
    string $label,
    string $expectation,
    string $identifier,
): void {
    expect($metric->version())->toBe(1)
        ->and($metric->label())->toBe($label)
        ->and($metric->expectation())->toBe($expectation)
        ->and($metric->identifier())->toBe($identifier);
})->with([
    'ccn2' => [Metric::Ccn2, 'Method complexity', 'toHaveMethodComplexityAtMost', 'ccn2@1'],
    'lines' => [Metric::Lines, 'Method lines', 'toHaveMethodLinesAtMost', 'lines@1'],
    'params' => [Metric::Params, 'Method parameters', 'toHaveMethodParametersAtMost', 'params@1'],
    'methods' => [Metric::Methods, 'Class methods', 'toHaveMethodsAtMost', 'methods@1'],
    'properties' => [Metric::Properties, 'Class properties', 'toHavePropertiesAtMost', 'properties@1'],
    'inheritance' => [Metric::Inheritance, 'Inheritance depth', 'toHaveInheritanceDepthAtMost', 'inheritance@1'],
    'classLines' => [Metric::ClassLines, 'Class lines', 'toHaveClassLinesAtMost', 'classLines@1'],
]);

it('backs each case with its stable string value and its scope', function (Metric $metric, string $value, Scope $scope): void {
    expect($metric->value)->toBe($value)
        ->and(Metric::from($value))->toBe($metric)
        ->and($metric->scope())->toBe($scope);
})->with([
    'ccn2' => [Metric::Ccn2, 'ccn2', Scope::Method],
    'lines' => [Metric::Lines, 'lines', Scope::Method],
    'params' => [Metric::Params, 'params', Scope::Method],
    'methods' => [Metric::Methods, 'methods', Scope::ClassLike],
    'properties' => [Metric::Properties, 'properties', Scope::ClassLike],
    'inheritance' => [Metric::Inheritance, 'inheritance', Scope::ClassLike],
    'classLines' => [Metric::ClassLines, 'classLines', Scope::ClassLike],
]);

it('names the plural noun of each scope', function (): void {
    expect(Scope::Method->plural())->toBe('methods')
        ->and(Scope::ClassLike->plural())->toBe('classes');
});
