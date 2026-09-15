<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Reporting\QualityReport;
use IanRodrigues\CodeQuality\Reporting\RunRecorder;
use JsonSchema\Validator;

/**
 * @return array{
 *     id: string,
 *     policy: string,
 *     location: array{file: string, line: int},
 *     targets: list<string>,
 *     metric: array{name: string, version: int},
 *     limit: int,
 *     directories: list<string>,
 *     exclusions: list<string>,
 *     coverage: array{filesFound: int, objects: int, withAst: int, methodsMeasured: int, classesMeasured: int, skipped: list<array{path: string, reason: string}>},
 *     baseline: array{path: string, applied: int, accepted: array<string, int>, stale: list<array{policy: string, symbol: string, metric: array{name: string, version: int}, limit: int, accepted: int, path: string}>}|null,
 *     measurements: list<array{symbol: string, path: string, line: int, ccn2: int|null, lines: int|null, params: int, methodName: int, variableName: int}>,
 *     violations: list<array{symbol: string, path: string, line: int, value: int, limit: int, contributions: list<array{label: string, line: int|null}>}>,
 *     errors: list<string>,
 * }
 */
function quality_entry(string $id, string $file, int $line, string $metric = 'ccn2'): array
{
    return [
        'id' => $id,
        'policy' => "{$id} :: {$metric}",
        'location' => ['file' => $file, 'line' => $line],
        'targets' => ['App\\Foo'],
        'metric' => ['name' => $metric, 'version' => 1],
        'limit' => 10,
        'directories' => ['app/Foo'],
        'exclusions' => [],
        'coverage' => [
            'filesFound' => 2,
            'objects' => 2,
            'withAst' => 2,
            'methodsMeasured' => 2,
            'classesMeasured' => 0,
            'skipped' => [],
        ],
        'baseline' => null,
        'measurements' => [
            ['symbol' => 'App\\Foo::z', 'path' => 'app/Foo/B.php', 'line' => 20, 'ccn2' => 3, 'lines' => 5, 'params' => 0, 'methodName' => 1, 'variableName' => 0],
            ['symbol' => 'App\\Foo::a', 'path' => 'app/Foo/A.php', 'line' => 10, 'ccn2' => 1, 'lines' => 2, 'params' => 1, 'methodName' => 1, 'variableName' => 0],
        ],
        'violations' => [],
        'errors' => [],
    ];
}

function quality_schema(): object
{
    /** @var object $schema */
    $schema = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/schema/quality-report.v1.json'));

    return $schema;
}

it('sorts policies by declaration site, then metric', function (): void {
    $report = QualityReport::from([
        quality_entry('second', 'tests/B.php', 5),
        quality_entry('first', 'tests/A.php', 20),
        quality_entry('third', 'tests/A.php', 20, 'lines'),
    ]);

    $ids = array_column($report->full()['policies'], 'id');

    expect($ids)->toBe(['first', 'third', 'second']);
});

it('sorts each policy\'s measurements and violations by path then symbol', function (): void {
    $entry = quality_entry('one', 'tests/A.php', 1);
    $entry['violations'] = [
        ['symbol' => 'App\\Foo::z', 'path' => 'app/Foo/B.php', 'line' => 20, 'value' => 11, 'limit' => 10, 'contributions' => []],
        ['symbol' => 'App\\Foo::a', 'path' => 'app/Foo/A.php', 'line' => 10, 'value' => 12, 'limit' => 10, 'contributions' => []],
    ];

    $report = QualityReport::from([$entry]);
    $policy = $report->full()['policies'][0];

    $measuredSymbols = array_column($policy['measurements'], 'symbol');
    $violatingSymbols = array_column($policy['violations'], 'symbol');

    expect($measuredSymbols)->toBe(['App\Foo::a', 'App\Foo::z'])
        ->and($violatingSymbols)->toBe(['App\Foo::a', 'App\Foo::z']);
});

it('carries a violation\'s contributions through to the full and findings-only reports', function (): void {
    $entry = quality_entry('one', 'tests/A.php', 1);
    $entry['violations'] = [
        [
            'symbol' => 'App\\Foo::z',
            'path' => 'app/Foo/B.php',
            'line' => 20,
            'value' => 11,
            'limit' => 10,
            'contributions' => [
                ['label' => 'if', 'line' => 21],
                ['label' => 'if', 'line' => 24],
            ],
        ],
    ];

    $report = QualityReport::from([$entry]);

    expect($report->full()['policies'][0]['violations'][0]['contributions'])->toBe([
        ['label' => 'if', 'line' => 21],
        ['label' => 'if', 'line' => 24],
    ])->and($report->findingsOnly()['policies'][0]['violations'][0]['contributions'])->toBe([
        ['label' => 'if', 'line' => 21],
        ['label' => 'if', 'line' => 24],
    ]);

    $document = json_decode(json_encode($report->full(), JSON_THROW_ON_ERROR));
    $validator = new Validator();
    $validator->validate($document, quality_schema());

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid');
});

it('carries the counted constructs on a violation row produced by a real policy run', function (): void {
    RunRecorder::reset();

    policy_failure(fn () => expect(FIXTURE_APP.'\Http\Controllers')->classes()->toHaveMethodComplexityAtMost(10));

    $document = QualityReport::from(RunRecorder::all())->full();
    $violation = $document['policies'][0]['violations'][0];

    expect($violation)->toHaveKey('contributions')
        ->and($violation['contributions'])->not->toBeEmpty();

    RunRecorder::reset();
});

it('omits measurements from the findings-only report', function (): void {
    $report = QualityReport::from([quality_entry('one', 'tests/A.php', 1)]);

    expect($report->full()['policies'][0])->toHaveKey('measurements')
        ->and($report->findingsOnly()['policies'][0])->not->toHaveKey('measurements');
});

it('stamps schemaVersion 1 and truncated false', function (): void {
    $full = QualityReport::from([quality_entry('one', 'tests/A.php', 1)])->full();

    expect($full)
        ->toHaveKey('schemaVersion', 1)
        ->toHaveKey('truncated', false)
        ->and($full['versions'])->toHaveKeys(['php', 'pest', 'plugin']);
});

it('prints "no policy ran" when nothing was recorded', function (): void {
    expect(QualityReport::from([])->toConsole())->toBe('Quality inspection: no policy ran.');
});

it('prints every policy, including passing ones, with its declaration site and measurements table', function (): void {
    $console = QualityReport::from([quality_entry('an example policy', 'tests/A.php', 1)])->toConsole();

    expect($console)
        ->toContain('an example policy')
        ->toContain('tests/A.php:1')
        ->toContain('Targets: App\Foo')
        ->toContain('Directories searched: app/Foo')
        ->toContain('Files found: 2  Objects: 2  With AST: 2  Methods measured: 2')
        ->toContain('App\Foo::a (app/Foo/A.php:10) ccn2=1 lines=2 params=1')
        ->toContain('App\Foo::z (app/Foo/B.php:20) ccn2=3 lines=5 params=0');
});

it('validates the full report produced by a real policy run against the shipped schema', function (): void {
    RunRecorder::reset();

    policy(fn () => expect(FIXTURE_APP.'\Billing')->classes()->toHaveMethodComplexityAtMost(99))();

    $document = json_decode(json_encode(QualityReport::from(RunRecorder::all())->full(), JSON_THROW_ON_ERROR));

    $validator = new Validator();
    $validator->validate($document, quality_schema());

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid');

    RunRecorder::reset();
});

it('validates the findings-only report produced by a real policy run against the shipped schema', function (): void {
    RunRecorder::reset();

    policy_failure(fn () => expect(FIXTURE_APP.'\Http\Controllers')->classes()->toHaveMethodComplexityAtMost(10));

    $document = json_decode(json_encode(QualityReport::from(RunRecorder::all())->findingsOnly(), JSON_THROW_ON_ERROR));

    $validator = new Validator();
    $validator->validate($document, quality_schema());

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid');

    RunRecorder::reset();
});

it('omits the baseline block entirely when no baseline is configured', function (): void {
    $report = QualityReport::from([quality_entry('one', 'tests/A.php', 1)]);

    expect($report->full()['policies'][0])->not->toHaveKey('baseline')
        ->and($report->findingsOnly()['policies'][0])->not->toHaveKey('baseline');
});

it('reports the baseline path, how many entries applied, and the stale ones', function (): void {
    $entry = quality_entry('one', 'tests/A.php', 1);
    $entry['baseline'] = [
        'path' => 'tests/quality-baseline.json',
        'applied' => 1,
        'accepted' => ['App\Foo::a' => 16],
        'stale' => [[
            'policy' => 'one :: ccn2',
            'symbol' => 'App\Foo::z',
            'metric' => ['name' => 'ccn2', 'version' => 1],
            'limit' => 12,
            'accepted' => 20,
            'path' => 'app/Foo/B.php',
        ]],
    ];

    $report = QualityReport::from([$entry]);
    $block = $report->full()['policies'][0]['baseline'] ?? null;

    expect($block)->toBe([
        'path' => 'tests/quality-baseline.json',
        'applied' => 1,
        'stale' => $entry['baseline']['stale'],
    ]);

    $document = json_decode(json_encode($report->full(), JSON_THROW_ON_ERROR));
    $validator = new Validator();
    $validator->validate($document, quality_schema());

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid');
});

it('shows the accepted value next to the measured one, and lists stale entries', function (): void {
    $entry = quality_entry('one', 'tests/A.php', 1);
    $entry['baseline'] = [
        'path' => 'tests/quality-baseline.json',
        'applied' => 1,
        'accepted' => ['App\Foo::a' => 16],
        'stale' => [[
            'policy' => 'one :: ccn2',
            'symbol' => 'App\Foo::z',
            'metric' => ['name' => 'ccn2', 'version' => 1],
            'limit' => 12,
            'accepted' => 20,
            'path' => 'app/Foo/B.php',
        ]],
    ];

    expect(QualityReport::from([$entry])->toConsole())
        ->toContain('Baseline: tests/quality-baseline.json (1 accepted, 1 stale)')
        ->toContain('stale: App\Foo::z (ccn2 v1, stored for limit 12, accepted 20)')
        ->toContain('App\Foo::a (app/Foo/A.php:10) ccn2=1 lines=2 params=1 accepted=16')
        ->toContain('App\Foo::z (app/Foo/B.php:20) ccn2=3 lines=5 params=0');
});
