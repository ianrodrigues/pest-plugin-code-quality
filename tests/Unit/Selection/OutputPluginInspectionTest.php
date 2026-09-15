<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Reporting\RunRecorder;
use IanRodrigues\CodeQuality\Selection\Plugins\OutputPlugin;
use IanRodrigues\CodeQuality\Selection\WarningsCollector;
use Pest\TestSuite;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @return array<array-key, mixed>
 */
function decoded_report(string $path): array
{
    $decoded = json_decode((string) file_get_contents($path), true);

    assert(is_array($decoded));

    return $decoded;
}

/**
 * @param array<array-key, mixed> $document
 * @return array<array-key, mixed>
 */
function first_policy(array $document): array
{
    assert(isset($document['policies']) && is_array($document['policies']));

    $policies = $document['policies'];

    assert(isset($policies[0]) && is_array($policies[0]));

    return $policies[0];
}

beforeEach(function (): void {
    RunRecorder::reset();
    WarningsCollector::reset();
});

afterEach(function (): void {
    RunRecorder::reset();
    WarningsCollector::reset();
});

it('strips a bare --quality-inspect flag from the arguments', function (): void {
    $plugin = new OutputPlugin(new BufferedOutput());

    $remaining = $plugin->handleArguments(['pest', '--quality-inspect', '--colors=never']);

    expect($remaining)->toBe(['pest', '--colors=never']);
});

it('strips --quality-inspect=path, carrying the path along', function (): void {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'quality-inspect-nested-'.uniqid();
    $path = $directory.DIRECTORY_SEPARATOR.'report.json';

    $output = new BufferedOutput();
    $plugin = new OutputPlugin($output);

    $remaining = $plugin->handleArguments(['pest', "--quality-inspect={$path}"]);

    expect($remaining)->toBe(['pest']);

    policy(fn () => expect(FIXTURE_APP.'\Billing')->classes()->toHaveMethodComplexityAtMost(99))();
    $plugin->addOutput(0);

    expect($output->fetch())->toBeEmpty()
        ->and(file_exists($path))->toBeTrue();

    unlink($path);
    rmdir($directory);
});

it('strips --quality-json=path independently of --quality-inspect', function (): void {
    $plugin = new OutputPlugin(new BufferedOutput());

    $remaining = $plugin->handleArguments(['pest', '--quality-json=findings.json', '--parallel']);

    expect($remaining)->toBe(['pest', '--parallel']);
});

it('prints the console inspection report, including passing policies, after the skipped-files summary', function (): void {
    policy(fn () => expect(FIXTURE_APP.'\Billing')->classes()->toHaveMethodComplexityAtMost(99))();

    $output = new BufferedOutput();
    $plugin = new OutputPlugin($output);
    $plugin->handleArguments(['--quality-inspect']);

    $plugin->addOutput(0);

    expect($output->fetch())
        ->toContain('Quality inspection')
        ->toContain('IanRodrigues\CodeQuality\Tests\Fixtures\App\Billing\Invoice::total');
});

it('never changes the exit code, regardless of the options passed', function (): void {
    $plugin = new OutputPlugin(new BufferedOutput());
    $plugin->handleArguments(['--quality-inspect']);

    expect($plugin->addOutput(7))->toBe(7);
});

it('writes the full report as JSON to --quality-inspect=path, printing nothing', function (): void {
    policy(fn () => expect(FIXTURE_APP.'\Billing')->classes()->toHaveMethodComplexityAtMost(99))();

    $path = tempnam(sys_get_temp_dir(), 'quality-inspect').'.json';
    $output = new BufferedOutput();
    $plugin = new OutputPlugin($output);
    $plugin->handleArguments(["--quality-inspect={$path}"]);

    $plugin->addOutput(0);

    expect($output->fetch())->toBeEmpty();

    $policy = first_policy(decoded_report($path));

    expect($policy['measurements'])->not->toBeEmpty();

    unlink($path);
});

it('writes findings only, without measurements, to --quality-json=path', function (): void {
    policy(fn () => expect(FIXTURE_APP.'\Billing')->classes()->toHaveMethodComplexityAtMost(99))();

    $path = tempnam(sys_get_temp_dir(), 'quality-json').'.json';
    $plugin = new OutputPlugin(new BufferedOutput());
    $plugin->handleArguments(["--quality-json={$path}"]);

    $plugin->addOutput(0);

    $policy = first_policy(decoded_report($path));

    expect($policy)->not->toHaveKey('measurements')
        ->toHaveKey('violations');

    unlink($path);
});

it('merges a worker\'s persisted partial into the orchestrator\'s report, then cleans it up', function (): void {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pest-quality-'.md5(TestSuite::getInstance()->rootPath);
    @mkdir($directory, 0777, true);

    $partial = [[
        'id' => 'a worker test',
        'policy' => 'a worker test :: ccn2',
        'location' => ['file' => 'tests/Worker.php', 'line' => 1],
        'targets' => ['App\\Worker'],
        'metric' => ['name' => 'ccn2', 'version' => 1],
        'limit' => 10,
        'directories' => [],
        'exclusions' => [],
        'coverage' => [
            'filesFound' => 1,
            'objects' => 1,
            'withAst' => 1,
            'methodsMeasured' => 1,
            'classesMeasured' => 0,
            'skipped' => [],
        ],
        'baseline' => null,
        'measurements' => [['symbol' => 'App\\Worker::run', 'path' => 'app/Worker.php', 'line' => 3, 'ccn2' => 1, 'lines' => 1, 'params' => 0]],
        'violations' => [[
            'symbol' => 'App\\Worker::run',
            'path' => 'app/Worker.php',
            'line' => 3,
            'value' => 12,
            'limit' => 10,
            'contributions' => [['label' => 'if', 'line' => 5], ['label' => '&&', 'line' => 5]],
        ]],
        'errors' => [],
    ]];

    file_put_contents($directory.DIRECTORY_SEPARATOR.'worker-1.json', json_encode($partial, JSON_THROW_ON_ERROR));

    $report = sys_get_temp_dir().DIRECTORY_SEPARATOR.'quality-report-'.uniqid().'.json';

    $output = new BufferedOutput();
    $plugin = new OutputPlugin($output);
    $plugin->handleArguments(['--quality-inspect='.$report]);

    $plugin->addOutput(0);

    $policy = first_policy(decoded_report($report));
    @unlink($report);

    expect($directory)->not->toBeDirectory()
        ->and($policy['id'])->toBe('a worker test')
        ->and($policy['violations'])->toBe([[
            'symbol' => 'App\\Worker::run',
            'path' => 'app/Worker.php',
            'line' => 3,
            'value' => 12,
            'limit' => 10,
            'contributions' => [['label' => 'if', 'line' => 5], ['label' => '&&', 'line' => 5]],
        ]]);
});
