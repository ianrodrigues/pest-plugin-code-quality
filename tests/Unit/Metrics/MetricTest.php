<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Metrics\Metric;

it('reports every case exactly once, so a new one forces a new dataset row', function (): void {
    expect(Metric::cases())->toHaveCount(3);
});

it('exposes version, label, expectation and identifier per case', function (
    Metric $metric,
    int $version,
    string $label,
    string $expectation,
    string $identifier,
): void {
    expect($metric->version())->toBe($version)
        ->and($metric->label())->toBe($label)
        ->and($metric->expectation())->toBe($expectation)
        ->and($metric->identifier())->toBe($identifier);
})->with([
    'ccn2' => [Metric::Ccn2, 1, 'Method complexity', 'toHaveMethodComplexityAtMost', 'ccn2@1'],
    'lines' => [Metric::Lines, 1, 'Method lines', 'toHaveMethodLinesAtMost', 'lines@1'],
    'params' => [Metric::Params, 1, 'Method parameters', 'toHaveMethodParametersAtMost', 'params@1'],
]);

it('backs each case with its stable string value', function (Metric $metric, string $value): void {
    expect($metric->value)->toBe($value)
        ->and(Metric::from($value))->toBe($metric);
})->with([
    'ccn2' => [Metric::Ccn2, 'ccn2'],
    'lines' => [Metric::Lines, 'lines'],
    'params' => [Metric::Params, 'params'],
]);
