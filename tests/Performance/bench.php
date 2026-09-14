<?php

declare(strict_types=1);

/**
 * Generates the full 50,000-line project and benchmarks this package
 * against it: engine-only, end-to-end cold, end-to-end with a baseline
 * configured, and end-to-end under `--parallel`. Prints a Markdown table
 * to stdout and, with `--json=path`, writes the same figures as JSON.
 *
 * Usage: php tests/Performance/bench.php [--json=path] [--seed=42]
 */

use IanRodrigues\CodeQuality\Tests\Performance\PerfProject;

$packageRoot = dirname(__DIR__, 2);

require $packageRoot.'/vendor/autoload.php';
require __DIR__.'/generate-project.php';

const PERF_RUNS = 3;
const PERF_COLD_TARGET_SECONDS = 10.0;
const PERF_WARM_TARGET_SECONDS = 2.0;
const PERF_PEAK_RSS_TARGET_BYTES = 512 * 1024 * 1024;

/**
 * @return array{json: ?string, seed: int}
 */
function perf_bench_options(): array
{
    global $argv;

    $json = null;
    $seed = 42;

    foreach (array_slice($argv, 1) as $argument) {
        if (str_starts_with($argument, '--json=')) {
            $json = substr($argument, strlen('--json='));
        }

        if (str_starts_with($argument, '--seed=')) {
            $seed = (int) substr($argument, strlen('--seed='));
        }
    }

    return ['json' => $json, 'seed' => $seed];
}

/**
 * @param list<string> $command
 * @param array<string, string> $env
 * @return array{seconds: ?float, rssBytes: ?int, exitCode: int, stdout: string, stderr: string}
 */
function perf_run_timed(array $command, string $cwd, array $env): array
{
    $timeFlag = PHP_OS_FAMILY === 'Darwin' ? '-l' : '-v';
    $full = [...['/usr/bin/time', $timeFlag], ...$command];

    foreach ($env as $name => $value) {
        putenv("{$name}={$value}");
    }

    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = proc_open($full, $descriptors, $pipes, $cwd);

    if ($process === false) {
        throw new RuntimeException('Could not start '.implode(' ', $full));
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    foreach (array_keys($env) as $name) {
        putenv($name);
    }

    ['seconds' => $seconds, 'rssBytes' => $rssBytes] = perf_parse_time_output($stderr);

    return ['seconds' => $seconds, 'rssBytes' => $rssBytes, 'exitCode' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr];
}

/**
 * @return array{seconds: ?float, rssBytes: ?int}
 */
function perf_parse_time_output(string $output): array
{
    if (preg_match('/^\s*([\d.]+)\s+real/m', $output, $match) === 1) {
        return [
            'seconds' => (float) $match[1],
            'rssBytes' => perf_match_int('/^\s*(\d+)\s+maximum resident set size/m', $output),
        ];
    }

    if (preg_match('/Elapsed \(wall clock\) time.*?:\s*([\d:.]+)/', $output, $match) === 1) {
        $kilobytes = perf_match_int('/Maximum resident set size \(kbytes\):\s*(\d+)/', $output);

        return [
            'seconds' => perf_parse_elapsed($match[1]),
            'rssBytes' => $kilobytes !== null ? $kilobytes * 1024 : null,
        ];
    }

    return ['seconds' => null, 'rssBytes' => null];
}

function perf_match_int(string $pattern, string $subject): ?int
{
    if (preg_match($pattern, $subject, $match) !== 1) {
        return null;
    }

    return (int) $match[1];
}

/**
 * Linux's GNU `time -v` reports elapsed wall clock time as `m:ss.hh` or
 * `h:mm:ss`.
 */
function perf_parse_elapsed(string $elapsed): float
{
    $parts = array_map(floatval(...), explode(':', $elapsed));
    $seconds = 0.0;

    foreach ($parts as $part) {
        $seconds = $seconds * 60 + $part;
    }

    return $seconds;
}

/**
 * @param callable(): array{seconds: ?float, rssBytes: ?int, exitCode: int, stdout: string, stderr: string} $run
 * @return array{seconds: float, rssBytes: int, runs: list<array{seconds: float, rssBytes: int}>}
 */
function perf_median_of(callable $run): array
{
    $samples = [];

    for ($i = 0; $i < PERF_RUNS; $i++) {
        $result = $run();

        if ($result['seconds'] === null || $result['rssBytes'] === null) {
            throw new RuntimeException(
                "Could not parse /usr/bin/time output:\n".$result['stderr'],
            );
        }

        $samples[] = ['seconds' => $result['seconds'], 'rssBytes' => $result['rssBytes']];
    }

    $sorted = $samples;
    usort($sorted, static fn (array $a, array $b): int => $a['seconds'] <=> $b['seconds']);
    $median = $sorted[intdiv(count($sorted), 2)];

    return ['seconds' => $median['seconds'], 'rssBytes' => $median['rssBytes'], 'runs' => $samples];
}

function perf_os_description(): string
{
    return php_uname('s').' '.php_uname('r').' ('.php_uname('m').')';
}

function perf_cpu_model(): string
{
    if (PHP_OS_FAMILY === 'Darwin') {
        return trim((string) shell_exec('sysctl -n machdep.cpu.brand_string 2>/dev/null')) ?: 'unknown';
    }

    $cpuinfo = @file_get_contents('/proc/cpuinfo');

    if (is_string($cpuinfo) && preg_match('/model name\s*:\s*(.+)/', $cpuinfo, $match) === 1) {
        return trim($match[1]);
    }

    return 'unknown';
}

function perf_commit_sha(string $packageRoot): string
{
    $sha = trim((string) shell_exec('git -C '.escapeshellarg($packageRoot).' rev-parse HEAD 2>/dev/null'));

    return $sha !== '' ? $sha : 'unknown';
}

function perf_bytes_to_mb(int $bytes): float
{
    return $bytes / (1024 * 1024);
}

/**
 * @param array{seconds: float, rssBytes: int, runs: list<array{seconds: float, rssBytes: int}>} $result
 */
function perf_status(array $result, ?float $timeTargetSeconds): string
{
    $overMemory = $result['rssBytes'] > PERF_PEAK_RSS_TARGET_BYTES;
    $overTime = $timeTargetSeconds !== null && $result['seconds'] > $timeTargetSeconds;

    return $overMemory || $overTime ? 'MISSED' : 'met';
}

/**
 * @param list<array{scenario: string, result: array{seconds: float, rssBytes: int, runs: list<array{seconds: float, rssBytes: int}>}, timeTarget: ?float}> $rows
 * @param array{files: int, lines: int} $project
 */
function perf_render_markdown(array $rows, string $os, string $cpu, string $phpVersion, string $commit, array $project): string
{
    $lines = [];
    $lines[] = '## Performance';
    $lines[] = '';
    $lines[] = "Runner: {$os} — {$cpu}";
    $lines[] = "PHP {$phpVersion} — plugin commit `{$commit}`";
    $lines[] = "Generated project: {$project['files']} files, {$project['lines']} lines";
    $lines[] = '';
    $lines[] = '| Scenario | Median wall time | Peak RSS | Target | Status |';
    $lines[] = '| --- | --- | --- | --- | --- |';

    foreach ($rows as $row) {
        $seconds = number_format($row['result']['seconds'], 2);
        $mb = number_format(perf_bytes_to_mb($row['result']['rssBytes']), 1);
        $target = $row['timeTarget'] !== null
            ? '≤ '.number_format($row['timeTarget'], 0).' s, ≤ 512 MB'
            : '≤ 512 MB';

        $lines[] = "| {$row['scenario']} | {$seconds} s | {$mb} MB | {$target} | ".perf_status($row['result'], $row['timeTarget']).' |';
    }

    return implode("\n", $lines)."\n";
}

// --- orchestration -----------------------------------------------------

$options = perf_bench_options();
$projectDirectory = sys_get_temp_dir().'/pest-quality-perf-'.uniqid();

fwrite(STDERR, "Generating project into {$projectDirectory}...\n");
$project = perf_generate_project($projectDirectory, $options['seed'], PERF_PROFILE_FULL);
fwrite(STDERR, "Generated {$project['files']} files, {$project['lines']} lines.\n");

fwrite(STDERR, "Installing generated project dependencies...\n");
PerfProject::install($projectDirectory);
PerfProject::sync($projectDirectory);

try {
    $phpBinary = PHP_BINARY;
    $baselinePath = $projectDirectory.'/quality-baseline.json';

    fwrite(STDERR, "Benchmarking engine-only...\n");
    $engineOnly = perf_median_of(static fn (): array => perf_run_timed(
        [$phpBinary, '-d', 'xdebug.mode=off', __DIR__.'/engine-only-runner.php', $packageRoot, $projectDirectory],
        $packageRoot,
        ['XDEBUG_MODE' => 'off'],
    ));

    fwrite(STDERR, "Benchmarking end-to-end (cold, no baseline)...\n");
    $cold = perf_median_of(static fn (): array => perf_run_timed(
        [$phpBinary, '-d', 'xdebug.mode=off', 'vendor/bin/pest', '--colors=never'],
        $projectDirectory,
        ['XDEBUG_MODE' => 'off', 'PERF_BASELINE_PATH' => ''],
    ));

    fwrite(STDERR, "Generating a baseline...\n");
    perf_run_timed(
        [$phpBinary, '-d', 'xdebug.mode=off', 'vendor/bin/pest', '--quality-baseline-generate', '--colors=never'],
        $projectDirectory,
        ['XDEBUG_MODE' => 'off', 'PERF_BASELINE_PATH' => $baselinePath],
    );

    if (! is_file($baselinePath)) {
        throw new RuntimeException("Baseline was not written to {$baselinePath}.");
    }

    fwrite(STDERR, "Benchmarking end-to-end (baseline configured)...\n");
    $baseline = perf_median_of(static fn (): array => perf_run_timed(
        [$phpBinary, '-d', 'xdebug.mode=off', 'vendor/bin/pest', '--colors=never'],
        $projectDirectory,
        ['XDEBUG_MODE' => 'off', 'PERF_BASELINE_PATH' => $baselinePath],
    ));

    fwrite(STDERR, "Benchmarking end-to-end (--parallel)...\n");
    $parallel = perf_median_of(static fn (): array => perf_run_timed(
        [$phpBinary, '-d', 'xdebug.mode=off', 'vendor/bin/pest', '--parallel', '--colors=never'],
        $projectDirectory,
        ['XDEBUG_MODE' => 'off', 'PERF_BASELINE_PATH' => ''],
    ));

    $rows = [
        ['scenario' => 'Engine-only (AstMeasurer, one process)', 'result' => $engineOnly, 'timeTarget' => null],
        ['scenario' => 'End-to-end, cold (no baseline)', 'result' => $cold, 'timeTarget' => PERF_COLD_TARGET_SECONDS],
        ['scenario' => 'End-to-end, baseline configured ("warm")', 'result' => $baseline, 'timeTarget' => PERF_WARM_TARGET_SECONDS],
        ['scenario' => 'End-to-end, --parallel (cold)', 'result' => $parallel, 'timeTarget' => null],
    ];

    $os = perf_os_description();
    $cpu = perf_cpu_model();
    $phpVersion = PHP_VERSION;
    $commit = perf_commit_sha($packageRoot);

    $markdown = perf_render_markdown($rows, $os, $cpu, $phpVersion, $commit, $project);

    fwrite(STDOUT, $markdown);

    if ($options['json'] !== null) {
        $document = [
            'generatedAt' => date(DATE_ATOM),
            'runner' => ['os' => $os, 'cpu' => $cpu, 'phpVersion' => $phpVersion, 'commit' => $commit],
            'project' => $project,
            'targets' => [
                'coldSeconds' => PERF_COLD_TARGET_SECONDS,
                'warmSeconds' => PERF_WARM_TARGET_SECONDS,
                'peakRssBytes' => PERF_PEAK_RSS_TARGET_BYTES,
            ],
            'results' => array_map(static fn (array $row): array => [
                'scenario' => $row['scenario'],
                'medianSeconds' => $row['result']['seconds'],
                'peakRssBytes' => $row['result']['rssBytes'],
                'timeTargetSeconds' => $row['timeTarget'],
                'status' => perf_status($row['result'], $row['timeTarget']),
                'runs' => $row['result']['runs'],
            ], $rows),
        ];

        file_put_contents($options['json'], (string) json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fwrite(STDERR, "Wrote JSON to {$options['json']}\n");
    }
} finally {
    perf_remove_directory($projectDirectory);
}
