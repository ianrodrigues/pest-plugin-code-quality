<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Tests\Support\FixtureProject;
use Symfony\Component\Finder\Finder;

/**
 * The whole adoption story, driven through the real Pest binary against a
 * project this file rewrites between tests. Every scenario lives here, in
 * one file, so that no two of them ever share that project at once.
 */
const ADOPTION_POLICY = 'legacy stays put';

const ADOPTION_BASELINE = 'quality-baseline.json';

function adoption_path(string $relative = ''): string
{
    $path = FixtureProject::path('Adoption');

    return $relative === '' ? $path : $path.'/'.$relative;
}

function adoption_write(string $relative, string $contents): void
{
    $path = adoption_path($relative);

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, $contents);
}

function adoption_clean(): void
{
    foreach (['app', 'tests'] as $directory) {
        if (! is_dir(adoption_path($directory))) {
            continue;
        }

        foreach (Finder::create()->files()->in(adoption_path($directory)) as $file) {
            unlink($file->getPathname());
        }
    }

    foreach (glob(adoption_path('quality-baseline*.json*')) ?: [] as $file) {
        unlink($file);
    }
}

/**
 * A method's `ccn2` is one plus the number of `if`s in it, which makes the
 * measured value of the generated class exactly what a scenario asks for.
 *
 * @param array<string, int> $methods method name to complexity
 */
function adoption_class(string $class, array $methods): void
{
    $body = '';

    foreach ($methods as $name => $complexity) {
        $body .= "\n    public function {$name}(int \$value): int\n    {\n";

        for ($branch = 1; $branch < $complexity; $branch++) {
            $body .= "        if (\$value === {$branch}) {\n            \$value++;\n        }\n\n";
        }

        $body .= "        return \$value;\n    }\n";
    }

    adoption_write(
        "app/{$class}.php",
        "<?php\n\ndeclare(strict_types=1);\n\nnamespace Fixture\\App;\n\nfinal class {$class}\n{{$body}}\n",
    );
}

function adoption_policy(
    int $limit = 10,
    string $description = ADOPTION_POLICY,
    string $file = 'tests/PolicyTest.php',
    string $target = 'Fixture\App',
): void {
    adoption_write($file, implode("\n", [
        '<?php',
        '',
        'declare(strict_types=1);',
        '',
        "arch('{$description}')",
        "    ->expect('{$target}')",
        '    ->classes()',
        "    ->toHaveMethodComplexityAtMost({$limit});",
        '',
    ]));
}

/**
 * @param list<array{symbol: string, accepted: int, limit?: int, version?: int, policy?: string}> $entries
 */
function adoption_baseline(array $entries, string $file = ADOPTION_BASELINE): void
{
    $rows = array_map(static fn (array $entry): array => [
        'policy' => $entry['policy'] ?? ADOPTION_POLICY.' :: ccn2',
        'symbol' => $entry['symbol'],
        'metric' => ['name' => 'ccn2', 'version' => $entry['version'] ?? 1],
        'limit' => $entry['limit'] ?? 10,
        'accepted' => $entry['accepted'],
        'path' => 'app/Legacy.php',
    ], $entries);

    adoption_write($file, (string) json_encode([
        'schemaVersion' => 1,
        'generatedAt' => '2026-01-01T00:00:00Z',
        'pluginVersion' => '0.1.0',
        'entries' => $rows,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * @param list<string> $arguments
 * @return array{exitCode: int, output: string}
 */
function adoption_run(array $arguments = []): array
{
    return FixtureProject::runPest(
        ['--colors=never', '--quality-baseline='.ADOPTION_BASELINE, ...$arguments],
        'Adoption',
    );
}

/**
 * @return array<array-key, mixed>
 */
function adoption_written_baseline(string $file = ADOPTION_BASELINE): array
{
    $decoded = json_decode((string) file_get_contents(adoption_path($file)), true);

    assert(is_array($decoded));

    return $decoded;
}

/**
 * @return array<string, int> symbol to accepted value
 */
function adoption_accepted(string $file = ADOPTION_BASELINE): array
{
    $entries = adoption_written_baseline($file)['entries'];

    assert(is_array($entries));

    $accepted = [];

    foreach ($entries as $entry) {
        assert(is_array($entry) && is_string($entry['symbol']) && is_int($entry['accepted']));

        $accepted[$entry['symbol']] = $entry['accepted'];
    }

    return $accepted;
}

beforeEach(function (): void {
    FixtureProject::ensureInstalled('Adoption');
    adoption_clean();
});

afterAll(function (): void {
    adoption_clean();
});

it('passes while an existing method stays at its accepted value, and shows it in inspection', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $result = adoption_run(['--quality-inspect']);

    expect($result['exitCode'])->toBe(0)
        ->and($result['output'])
        ->toContain('Tests:    1 passed')
        ->toContain('Baseline: quality-baseline.json (1 accepted, 0 stale)')
        ->toContain('Fixture\App\Legacy::handle (app/Legacy.php:9) ccn2=16')
        ->toContain('accepted=16');
});

it('fails with the increase spelled out when an existing method grows past its accepted value', function (): void {
    adoption_class('Legacy', ['handle' => 17]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('Method complexity (ccn2 v1): 17')
        ->toContain('Allowed: at most 10')
        ->toContain('Accepted: 16')
        ->toContain('Increase: 1');
});

it('passes when an improved method is still above the limit, and tightening lowers its entry', function (): void {
    adoption_class('Legacy', ['handle' => 14]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    expect(adoption_run()['exitCode'])->toBe(0);

    $tighten = adoption_run(['--quality-baseline-tighten']);

    expect($tighten['output'])->toContain('~ Fixture\App\Legacy::handle (ccn2 16 -> 14)')
        ->and(adoption_accepted())->toBe(['Fixture\App\Legacy::handle' => 14]);
});

it('fails once a tightened method grows again', function (): void {
    adoption_class('Legacy', ['handle' => 15]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 14]]);

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('Accepted: 14')
        ->toContain('Increase: 1');
});

it('fails on a new method above the limit, with no accepted value to lean on', function (): void {
    adoption_class('Legacy', ['handle' => 16, 'arrived' => 11]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('Fixture\App\Legacy::arrived')
        ->toContain('Method complexity (ccn2 v1): 11')
        ->not->toContain('Accepted: 11');
});

it('passes when a method drops back within the limit, and tightening removes its entry', function (): void {
    adoption_class('Legacy', ['handle' => 9]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    expect(adoption_run()['exitCode'])->toBe(0);

    $tighten = adoption_run(['--quality-baseline-tighten']);

    expect($tighten['output'])->toContain('- Fixture\App\Legacy::handle (ccn2)')
        ->and(adoption_accepted())->toBeEmpty();
});

it('treats a renamed method as new, never transferring its allowance', function (): void {
    adoption_class('Legacy', ['process' => 16]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('Fixture\App\Legacy::process')
        ->not->toContain('Accepted:');
});

it('errors when the configured baseline is missing', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('does not exist');
});

it('errors when the configured baseline is corrupt', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_write(ADOPTION_BASELINE, '{"schemaVersion": 1, "entries": [');

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('could not be read');
});

it('leaves the baseline on disk untouched when the write cannot complete', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $before = (string) file_get_contents(adoption_path(ADOPTION_BASELINE));

    chmod(adoption_path(), 0555);

    try {
        $result = adoption_run(['--quality-baseline-generate']);
    } finally {
        chmod(adoption_path(), 0755);
    }

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('the file on disk is unchanged')
        ->and(file_get_contents(adoption_path(ADOPTION_BASELINE)))->toBe($before);
});

it('errors when two policies resolve to the same identity, naming both', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_policy(file: 'tests/TwinTest.php');
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('resolve to the same identity')
        ->toContain('tests/PolicyTest.php')
        ->toContain('tests/TwinTest.php');
});

it('keeps entries valid when the test file moves', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy(file: 'tests/Nested/MovedTest.php');
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    expect(adoption_run()['exitCode'])->toBe(0);
});

it('marks entries stale when the configured limit changes', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy(limit: 12);
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16, 'limit' => 10]]);

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('1 entry no longer matches its policy and was ignored')
        ->toContain('Fixture\App\Legacy::handle')
        ->toContain('stored for limit 10');
});

it('marks entries stale when the metric version changes', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16, 'version' => 2]]);

    $result = adoption_run(['--quality-inspect']);

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('Baseline: quality-baseline.json (0 accepted, 1 stale)')
        ->toContain('stale: Fixture\App\Legacy::handle (ccn2 v2, stored for limit 10, accepted 16)');
});

it('generates a baseline that makes the next run pass', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();

    $generate = adoption_run(['--quality-baseline-generate']);

    expect($generate['output'])
        ->toContain('Quality baseline written: quality-baseline.json')
        ->toContain('added 1, removed 0, increased 0, decreased 0')
        ->and(adoption_accepted())->toBe(['Fixture\App\Legacy::handle' => 16])
        ->and(adoption_run()['exitCode'])->toBe(0);
});

it('preserves the entries of policies a filtered run never looked at', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_class('Other', ['handle' => 16]);
    adoption_policy(target: 'Fixture\App\Legacy');
    adoption_policy(description: 'other stays put', file: 'tests/OtherTest.php', target: 'Fixture\App\Other');
    adoption_baseline([
        ['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16],
        ['symbol' => 'Fixture\App\Other::handle', 'accepted' => 16, 'policy' => 'other stays put :: ccn2'],
    ]);

    $result = adoption_run(['--quality-baseline-generate', '--filter=legacy stays put']);

    expect($result['exitCode'])->toBe(0)
        ->and(adoption_accepted())->toBe([
            'Fixture\App\Legacy::handle' => 16,
            'Fixture\App\Other::handle' => 16,
        ]);
});

it('refuses to generate or tighten when a policy errored', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_policy(description: 'nothing to see', file: 'tests/GhostTest.php', target: 'Fixture\App\Ghost');
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $before = (string) file_get_contents(adoption_path(ADOPTION_BASELINE));

    foreach (['--quality-baseline-generate', '--quality-baseline-tighten'] as $option) {
        $result = adoption_run([$option]);

        expect($result['exitCode'])->not->toBe(0)
            ->and($result['output'])->toContain('a policy did not finish')
            ->and(file_get_contents(adoption_path(ADOPTION_BASELINE)))->toBe($before);
    }
});

it('refuses to generate when no baseline is configured at all', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();

    $result = FixtureProject::runPest(['--colors=never', '--quality-baseline-generate'], 'Adoption');

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('No quality baseline is configured')
        ->and(file_exists(adoption_path(ADOPTION_BASELINE)))->toBeFalse();
});

it('refuses to generate and tighten in the same run', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);

    $result = adoption_run(['--quality-baseline-generate', '--quality-baseline-tighten']);

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('cannot be used in the same run');
});

it('generates the same baseline serially and in parallel', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_class('Other', ['handle' => 21]);
    adoption_policy(target: 'Fixture\App\Legacy');
    adoption_policy(description: 'other stays put', file: 'tests/OtherTest.php', target: 'Fixture\App\Other');

    adoption_run(['--quality-baseline-generate']);
    $serial = adoption_written_baseline();

    adoption_run(['--quality-baseline-generate', '--parallel']);
    $parallel = adoption_written_baseline();

    unset($serial['generatedAt'], $parallel['generatedAt']);

    expect($parallel)->toBe($serial)
        ->and(adoption_accepted())->toBe([
            'Fixture\App\Legacy::handle' => 16,
            'Fixture\App\Other::handle' => 21,
        ]);
});

it('reads the baseline configured in tests/Pest.php, and lets the command line override it', function (): void {
    adoption_class('Legacy', ['handle' => 16]);
    adoption_policy();
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 16]]);
    adoption_baseline([['symbol' => 'Fixture\App\Legacy::handle', 'accepted' => 11]], 'quality-baseline-low.json');
    adoption_write('tests/Pest.php', implode("\n", [
        '<?php',
        '',
        'declare(strict_types=1);',
        '',
        "\\IanRodrigues\\CodeQuality\\Config::baseline(__DIR__.'/../".ADOPTION_BASELINE."');",
        '',
    ]));

    $configured = FixtureProject::runPest(['--colors=never'], 'Adoption');
    $overridden = FixtureProject::runPest(
        ['--colors=never', '--quality-baseline=quality-baseline-low.json'],
        'Adoption',
    );

    expect($configured['exitCode'])->toBe(0)
        ->and($overridden['exitCode'])->not->toBe(0)
        ->and($overridden['output'])->toContain('Accepted: 11');
});

it('carries a class symbol through generate, then fails once the class grows', function (): void {
    adoption_class('Legacy', ['handle' => 1, 'process' => 1, 'report' => 1]);
    adoption_write('tests/PolicyTest.php', implode("\n", [
        '<?php',
        '',
        'declare(strict_types=1);',
        '',
        "arch('legacy stays narrow')",
        "    ->expect('Fixture\\App')",
        '    ->classes()',
        '    ->toHaveMethodsAtMost(2);',
        '',
    ]));

    adoption_run(['--quality-baseline-generate']);

    expect(adoption_accepted())->toBe(['Fixture\App\Legacy' => 3])
        ->and(adoption_run()['exitCode'])->toBe(0);

    adoption_class('Legacy', ['handle' => 1, 'process' => 1, 'report' => 1, 'archive' => 1]);

    $result = adoption_run();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('Fixture\App\Legacy')
        ->toContain('Class methods (methods v1): 4')
        ->toContain('Accepted: 3')
        ->toContain('1 class exceeds the limit');
});
