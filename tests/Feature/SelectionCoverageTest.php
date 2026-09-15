<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Config;
use IanRodrigues\CodeQuality\Policies\Policy;
use IanRodrigues\CodeQuality\Policies\PolicyResult;
use IanRodrigues\CodeQuality\Policies\PolicyRunner;
use IanRodrigues\CodeQuality\Policies\Targets;
use IanRodrigues\CodeQuality\Selection\Coverage;
use IanRodrigues\CodeQuality\Selection\EmptySelection;
use IanRodrigues\CodeQuality\Selection\SkippedFilesFound;
use IanRodrigues\CodeQuality\Selection\SkipReason;
use IanRodrigues\CodeQuality\Selection\VendorTarget;
use IanRodrigues\CodeQuality\Selection\WarningsCollector;
use Pest\Arch\Options\LayerOptions;
use Pest\Arch\SingleArchExpectation;
use PhpParser\Node\Expr;

/**
 * `PolicyResult::$coverage` is nullable only for backward compatibility
 * with code built before completeness accounting existed; every result
 * `PolicyRunner` returns carries one.
 */
function coverage_of(PolicyResult $result): Coverage
{
    assert($result->coverage instanceof Coverage);

    return $result->coverage;
}

/**
 * Verifying the throwaway expectation here, synchronously, keeps its
 * lazy self-verification from firing later from `__destruct()` — where an
 * exception this suite intentionally provokes (`SkippedFilesFound` under
 * strict mode, for one) would otherwise become an uncatchable fatal error.
 *
 * @return array{PolicyRunner, Targets, LayerOptions}
 */
function selection_runner_for(string $target): array
{
    $expectation = expect($target)->classes()->toHaveMethodComplexityAtMost(99);

    assert($expectation instanceof SingleArchExpectation);

    $options = LayerOptions::fromExpectation($expectation);

    try {
        $expectation->ensureLazyExpectationIsVerified();
    } catch (Throwable) {
        // Only $options matters here; the throwaway's own outcome does not.
    }

    return [
        new PolicyRunner(),
        Targets::fromValues([$target]),
        $options,
    ];
}

beforeEach(function (): void {
    Config::reset();
    WarningsCollector::reset();
});

afterEach(function (): void {
    Config::reset();
    WarningsCollector::reset();
});

it('scopes coverage to the exact directory, never a sibling sharing its prefix', function (): void {
    [$runner, $targets, $options] = selection_runner_for(FIXTURE_APP.'\Billing');

    $result = $runner->run(Policy::complexity(99), $targets, $options);

    $coverage = coverage_of($result)->targets[0];

    expect($coverage->directories)->toBe(['tests/Fixtures/App/Billing'])
        ->and($coverage->filesFound)->toBe(1)
        ->and($coverage->objectsWithAst)->toBe(1)
        ->and($coverage->eligibleSymbols)->toBe(1)
        ->and($coverage->skipped)->toBeEmpty();
});

it('scopes coverage independently for every sibling sharing a prefix', function (string $target, string $directory): void {
    [$runner, $targets, $options] = selection_runner_for($target);

    $result = $runner->run(Policy::complexity(99), $targets, $options);

    $coverage = coverage_of($result)->targets[0];

    expect($coverage->directories)->toBe([$directory])
        ->and($coverage->filesFound)->toBe(1);
})->with([
    'Billing' => [FIXTURE_APP.'\Billing', 'tests/Fixtures/App/Billing'],
    'BillingArchive' => [FIXTURE_APP.'\BillingArchive', 'tests/Fixtures/App/BillingArchive'],
    'Billing2' => [FIXTURE_APP.'\Billing2', 'tests/Fixtures/App/Billing2'],
]);

it('errors when a target selects zero eligible methods', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')->classes()->toHaveMethodComplexityAtMost(10));

    expect($chain)->toThrow(EmptySelection::class);
});

it('names the target, directories and counts in the empty-selection error', function (): void {
    $message = null;

    try {
        policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')->classes()->toHaveMethodComplexityAtMost(10))();
    } catch (EmptySelection $exception) {
        $message = $exception->getMessage();
    }

    expect($message)
        ->toContain(FIXTURE_APP.'\GhostNamespace')
        ->toContain('tests/Fixtures/App/GhostNamespace')
        ->toContain('PHP files found: 1')
        ->toContain('allowEmpty');
});

it('classifies a namespace-mismatched file precisely', function (): void {
    [$runner, $targets, $options] = selection_runner_for(FIXTURE_APP.'\GhostNamespace');

    $result = $runner->run(Policy::complexity(99, allowEmpty: true), $targets, $options);

    $skipped = coverage_of($result)->skippedFiles();

    expect($skipped)->toHaveCount(1)
        ->and($skipped[0]->path)->toBe('tests/Fixtures/App/GhostNamespace/Service.php')
        ->and($skipped[0]->reason)->toBe(SkipReason::NamespaceMismatch);
});

it('passes an otherwise empty selection when allowEmpty opts out', function (): void {
    $chain = policy(fn () => expect(FIXTURE_APP.'\GhostNamespace')
        ->classes()
        ->toHaveMethodComplexityAtMost(10, allowEmpty: true));

    expect($chain)->not->toThrow(EmptySelection::class);
});

it('errors on a target that resolves entirely under vendor', function (): void {
    $chain = policy(fn () => expect(Expr::class)->classes()->toHaveMethodComplexityAtMost(10));

    expect($chain)->toThrow(VendorTarget::class);
});

it('carries a warning for a file that could not be loaded, without failing the policy', function (): void {
    [$runner, $targets, $options] = selection_runner_for(FIXTURE_APP.'\PartialLoad');

    $result = $runner->run(Policy::complexity(99), $targets, $options);

    expect($result->passed())->toBeTrue();

    $skipped = coverage_of($result)->skippedFiles();

    expect($skipped)->toHaveCount(1)
        ->and($skipped[0]->path)->toBe('tests/Fixtures/App/PartialLoad/Broken.php')
        ->and($skipped[0]->reason)->toBe(SkipReason::NotLoadable);
});

it('errors instead of warning once strict mode is on', function (): void {
    Config::strict(true);

    [$runner, $targets, $options] = selection_runner_for(FIXTURE_APP.'\PartialLoad');

    expect(fn (): PolicyResult => $runner->run(Policy::complexity(99), $targets, $options))
        ->toThrow(SkippedFilesFound::class, 'Broken.php');
});

it('counts a functions file as found without treating it as skipped, and does not warn', function (): void {
    [$runner, $targets, $options] = selection_runner_for(FIXTURE_APP.'\FunctionsOnly');

    $result = $runner->run(Policy::complexity(99), $targets, $options);

    expect($result->passed())->toBeTrue();

    $coverage = coverage_of($result)->targets[0];

    expect($coverage->filesFound)->toBe(2)
        ->and($coverage->withoutClasses)->toBe(1)
        ->and($coverage->objectsWithAst)->toBe(1)
        ->and($coverage->eligibleSymbols)->toBe(1)
        ->and($coverage->skipped)->toBeEmpty()
        ->and(coverage_of($result)->skippedFiles())->toBeEmpty()
        ->and(WarningsCollector::all())->toBeEmpty();
});

it('classifies a genuinely unparsable file as no ast, not as a file without classes', function (): void {
    [$runner, $targets, $options] = selection_runner_for(FIXTURE_APP.'\Broken');

    $result = $runner->run(Policy::complexity(99, allowEmpty: true), $targets, $options);

    $coverage = coverage_of($result)->targets[0];

    expect($coverage->skipped)->toHaveCount(1)
        ->and($coverage->skipped[0]->path)->toBe('tests/Fixtures/App/Broken/SyntaxError.php')
        ->and($coverage->skipped[0]->reason)->toBe(SkipReason::NoAst)
        ->and($coverage->withoutClasses)->toBe(0);
});
