<?php

declare(strict_types=1);

use Pest\Arch\Options\LayerOptions;
use Pest\Arch\SingleArchExpectation;
use Rdgs\PestCodeQuality\Analysis\AstMeasurer;
use Rdgs\PestCodeQuality\Analysis\MeasurementCache;
use Rdgs\PestCodeQuality\Policies\Policy;
use Rdgs\PestCodeQuality\Policies\PolicyRunner;
use Rdgs\PestCodeQuality\Policies\Targets;

/**
 * @return array{PolicyRunner, Targets, LayerOptions, MeasurementCache}
 */
function runner_for(string $target): array
{
    $expectation = expect($target)->classes()->toHaveMethodComplexityAtMost(99);

    assert($expectation instanceof SingleArchExpectation);

    $cache = new MeasurementCache();

    return [
        new PolicyRunner(new AstMeasurer(), $cache),
        Targets::fromValues([$target]),
        LayerOptions::fromExpectation($expectation),
        $cache,
    ];
}

it('measures every object of the target once per policy run', function (): void {
    [$runner, $targets, $options] = runner_for(FIXTURE_APP.'\Http\Controllers');

    $result = $runner->run(Policy::complexity(10), $targets, $options);

    expect($result->objectsSeen)->toBe(3)
        ->and($result->methodsMeasured)->toBe(6)
        ->and($result->violations)->toHaveCount(2);
});

it('reuses the measurements of one target across several policies', function (): void {
    [$runner, $targets, $options, $cache] = runner_for(FIXTURE_APP.'\Http\Controllers');

    $runner->run(Policy::complexity(10), $targets, $options);
    $runner->run(Policy::lines(40), $targets, $options);
    $runner->run(Policy::parameters(4), $targets, $options);

    $path = (string) realpath(__DIR__.'/../Fixtures/App/Http/Controllers/CheckoutController.php');
    $contents = (string) file_get_contents($path);

    expect(fn () => $cache->remember($path, $contents, fn () => throw new RuntimeException('re-measured')))
        ->not->toThrow(RuntimeException::class);
});

it('counts only the methods the metric applies to', function (): void {
    [$runner, $targets, $options] = runner_for(FIXTURE_APP.'\Parsing');

    $complexity = $runner->run(Policy::complexity(99), $targets, $options);
    $parameters = $runner->run(Policy::parameters(99), $targets, $options);

    expect($complexity->methodsMeasured)->toBe(1)
        ->and($parameters->methodsMeasured)->toBe(1)
        ->and($complexity->passed())->toBeTrue();
});
