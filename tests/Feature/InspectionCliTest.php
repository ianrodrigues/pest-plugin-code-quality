<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Tests\Support\FixtureProject;
use JsonSchema\Validator;

/**
 * @return array<array-key, mixed>
 */
function fixture_json(string $path): array
{
    $decoded = json_decode((string) file_get_contents($path), true);

    assert(is_array($decoded));

    return $decoded;
}

/**
 * @param array<array-key, mixed> $document
 * @return array<array-key, mixed>
 */
function fixture_first_policy(array $document): array
{
    assert(isset($document['policies']) && is_array($document['policies']));

    $policies = $document['policies'];

    assert(isset($policies[0]) && is_array($policies[0]));

    return $policies[0];
}

/**
 * Strips fields that legitimately differ between two independent process
 * runs (a timestamp) so an otherwise identical report compares equal.
 *
 * @param array<array-key, mixed> $document
 * @return array<array-key, mixed>
 */
function without_generated_at(array $document): array
{
    unset($document['generatedAt']);

    return $document;
}

/**
 * Policy order depends on which worker measured what first under
 * `--parallel`; this makes both reports comparable regardless.
 *
 * @param array<array-key, mixed> $document
 * @return array<array-key, mixed>
 */
function with_sorted_policies(array $document): array
{
    $policies = $document['policies'];

    assert(is_array($policies));

    usort($policies, static function (mixed $a, mixed $b): int {
        assert(is_array($a) && is_array($b));

        return json_encode($a) <=> json_encode($b);
    });

    $document['policies'] = $policies;

    return $document;
}

it('prints every recorded policy, passing ones included, after the normal results', function (): void {
    $result = FixtureProject::runPest(['--quality-inspect', '--colors=never']);

    expect($result['exitCode'])->toBe(0)
        ->and($result['output'])
        ->toContain('Tests:    2 passed')
        ->toContain('Quality inspection')
        ->toContain('fixture app methods stay within limits')
        ->toContain('tests/ArchTest.php:8')
        ->toContain('Targets: Fixture\App')
        ->toContain('Files found: 2  Objects: 2  With AST: 2  Methods measured: 2')
        ->toContain('Fixture\App\Billing\Invoice::total (app/Billing/Invoice.php:9) ccn2=2 lines=4 params=1')
        ->toContain('Fixture\App\Reporting\Report::summarize (app/Reporting/Report.php:9) ccn2=3 lines=5 params=1');
});

it('prints a class-scoped policy with the class symbols it measured', function (): void {
    $result = FixtureProject::runPest(['--quality-inspect', '--colors=never']);

    expect($result['exitCode'])->toBe(0)
        ->and($result['output'])
        ->toContain('fixture app classes stay small')
        ->toContain('Metric: classLines v1, limit 20')
        ->toContain('Files found: 2  Objects: 2  With AST: 2  Classes measured: 2')
        ->toContain('Fixture\App\Billing\Invoice (app/Billing/Invoice.php:7) methods=1 properties=0 inheritance=0 classLines=5')
        ->toContain('Fixture\App\Reporting\Report (app/Reporting/Report.php:7) methods=1 properties=0 inheritance=0 classLines=6');
});

it('never changes the test outcome, with or without --quality-inspect', function (): void {
    $plain = FixtureProject::runPest(['--colors=never']);
    $inspected = FixtureProject::runPest(['--quality-inspect', '--colors=never']);

    expect($plain['exitCode'])->toBe(0)
        ->and($inspected['exitCode'])->toBe(0)
        ->and($plain['output'])->toContain('Tests:    2 passed')
        ->and($inspected['output'])->toContain('Tests:    2 passed');
});

it('writes the full report as JSON to --quality-inspect=path, valid against the shipped schema', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'quality-inspect').'.json';

    $result = FixtureProject::runPest(["--quality-inspect={$path}", '--colors=never']);

    expect($result['exitCode'])->toBe(0);

    $document = fixture_json($path);

    expect($document['policies'])->toHaveCount(4);

    $schema = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/schema/quality-report.v1.json'));
    $validator = new Validator();
    $decoded = json_decode((string) file_get_contents($path));
    $validator->validate($decoded, $schema);

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid');

    unlink($path);
});

it('writes findings only, without measurements, to --quality-json=path, valid against the shipped schema', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'quality-json').'.json';

    $result = FixtureProject::runPest(["--quality-json={$path}", '--colors=never']);

    expect($result['exitCode'])->toBe(0);

    $document = fixture_json($path);

    $policy = fixture_first_policy($document);

    expect($policy)->not->toHaveKey('measurements');

    $schema = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/schema/quality-report.v1.json'));
    $validator = new Validator();
    $decoded = json_decode((string) file_get_contents($path));
    $validator->validate($decoded, $schema);

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid');

    unlink($path);
});

it('produces the same report serial and parallel, once sorted', function (): void {
    $serialPath = tempnam(sys_get_temp_dir(), 'quality-serial').'.json';
    $parallelPath = tempnam(sys_get_temp_dir(), 'quality-parallel').'.json';

    $serial = FixtureProject::runPest(["--quality-inspect={$serialPath}", '--colors=never']);
    $parallel = FixtureProject::runPest(['--parallel', "--quality-inspect={$parallelPath}", '--colors=never']);

    expect($serial['exitCode'])->toBe(0)
        ->and($parallel['exitCode'])->toBe(0);

    $serialDocument = with_sorted_policies(without_generated_at(fixture_json($serialPath)));
    $parallelDocument = with_sorted_policies(without_generated_at(fixture_json($parallelPath)));

    expect($parallelDocument)->toBe($serialDocument);

    unlink($serialPath);
    unlink($parallelPath);
});

it('never changes the test outcome under --parallel either', function (): void {
    $result = FixtureProject::runPest(['--parallel', '--quality-inspect', '--colors=never']);

    expect($result['exitCode'])->toBe(0)
        ->and($result['output'])->toContain('Tests:    2 passed');
});
