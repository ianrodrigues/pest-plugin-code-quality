<?php

declare(strict_types=1);

/**
 * Writes a deterministic, production-sized throwaway PHP project to
 * benchmark this package against, with a deliberate share of methods over
 * the limits the generated project's own arch tests check.
 *
 * Seeded through `mt_srand()`, with every file built in a fixed order, so
 * the same seed and profile always write the same bytes to disk.
 */

const PERF_PROFILE_FULL = [
    'controllers' => 70,
    'services' => 70,
    'models' => 70,
    'jobs' => 70,
    'valueObjects' => 52,
    'concerns' => 9,
    'enums' => 9,
    'methodsRange' => [7, 12],
    'bodyLinesRange' => [3, 13],
];

const PERF_PROFILE_SMALL = [
    'controllers' => 5,
    'services' => 4,
    'models' => 4,
    'jobs' => 3,
    'valueObjects' => 5,
    'concerns' => 2,
    'enums' => 2,
    'methodsRange' => [4, 6],
    'bodyLinesRange' => [3, 9],
];

const PERF_NAMESPACE = 'PerfProject\\App';

/** @var list<string> */
const PERF_NOUNS = [
    'Order', 'Invoice', 'Customer', 'Payment', 'Shipment', 'Inventory',
    'Subscription', 'Report', 'Notification', 'Audit', 'Ledger', 'Catalog',
    'Warehouse', 'Voucher', 'Refund', 'Dispute', 'Contract', 'Appointment',
    'Ticket', 'Campaign', 'Onboarding', 'Renewal', 'Escalation', 'Delivery',
    'Reservation', 'Statement', 'Reconciliation', 'Withdrawal', 'Deposit', 'Quote',
];

/**
 * @param array{controllers: int, services: int, models: int, jobs: int, valueObjects: int, concerns: int, enums: int, methodsRange: array{0: int, 1: int}, bodyLinesRange: array{0: int, 1: int}} $profile
 * @return array{files: int, lines: int}
 */
function perf_generate_project(string $directory, int $seed = 42, array $profile = PERF_PROFILE_FULL): array
{
    perf_reset_directory($directory);
    mt_srand($seed);

    $state = ['methodIndex' => 0, 'files' => 0, 'lines' => 0];

    $enums = perf_generate_pool($directory, 'Enums', $profile['enums'], 'perf_write_enum');
    $concerns = perf_generate_pool($directory, 'Support/Concerns', $profile['concerns'], 'perf_write_concern');
    $valueObjects = perf_generate_pool($directory, 'ValueObjects', $profile['valueObjects'], 'perf_write_value_object');

    $refs = ['enums' => $enums, 'concerns' => $concerns, 'valueObjects' => $valueObjects];

    foreach (['Controllers' => 'controllers', 'Services' => 'services', 'Models' => 'models', 'Jobs' => 'jobs'] as $dir => $key) {
        for ($i = 0; $i < $profile[$key]; $i++) {
            $name = perf_class_name($dir, $i);
            $path = perf_write_kind_class($directory, $dir, $name, $refs, $profile, $state);
            $state['files']++;
            $state['lines'] += perf_count_lines($path);
        }
    }

    foreach ([$enums, $concerns, $valueObjects] as $pool) {
        foreach ($pool as $entry) {
            $state['files']++;
            $state['lines'] += perf_count_lines($entry['path']);
        }
    }

    perf_write_composer_json($directory);
    perf_write_phpunit_xml($directory);
    perf_write_pest_bootstrap($directory);
    perf_write_quality_test($directory);

    return ['files' => $state['files'], 'lines' => $state['lines']];
}

function perf_reset_directory(string $directory): void
{
    if (is_dir($directory)) {
        perf_remove_directory($directory);
    }

    mkdir($directory, 0777, true);
}

function perf_remove_directory(string $directory): void
{
    $items = scandir($directory);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory.'/'.$item;

        if (is_link($path) || is_file($path)) {
            unlink($path);

            continue;
        }

        perf_remove_directory($path);
    }

    rmdir($directory);
}

function perf_count_lines(string $path): int
{
    $contents = (string) file_get_contents($path);

    return substr_count($contents, "\n");
}

function perf_class_name(string $kindDir, int $index): string
{
    $noun = PERF_NOUNS[$index % count(PERF_NOUNS)];
    $cycle = intdiv($index, count(PERF_NOUNS));
    $suffix = match ($kindDir) {
        'Controllers' => 'Controller',
        'Services' => 'Service',
        'Models' => 'Model',
        'Jobs' => 'Job',
        default => '',
    };

    return $noun.$suffix.($cycle > 0 ? (string) ($cycle + 1) : '');
}

/**
 * @param callable(string, string, int): array{name: string, path: string} $writer
 * @return list<array{name: string, path: string}>
 */
function perf_generate_pool(string $directory, string $subPath, int $count, callable $writer): array
{
    $pool = [];

    for ($i = 0; $i < $count; $i++) {
        $pool[] = $writer($directory, $subPath, $i);
    }

    return $pool;
}

/**
 * @return array{name: string, path: string}
 */
function perf_write_enum(string $directory, string $subPath, int $index): array
{
    $name = PERF_NOUNS[$index % count(PERF_NOUNS)].'Status';
    $cycle = intdiv($index, count(PERF_NOUNS));
    $name .= $cycle > 0 ? (string) ($cycle + 1) : '';

    $namespace = PERF_NAMESPACE.'\\Enums';
    $cases = ['Pending', 'Active', 'Completed', 'Cancelled'];

    $caseLines = '';
    $matchArms = '';

    foreach ($cases as $case) {
        $value = strtolower($case);
        $caseLines .= "    case {$case} = '{$value}';\n";
        $matchArms .= "            self::{$case} => '".ucfirst($value)."',\n";
    }

    $source = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        enum {$name}: string
        {
        {$caseLines}
            public function label(): string
            {
                return match (\$this) {
        {$matchArms}        };
            }

            public function isFinal(): bool
            {
                return \$this === self::Completed || \$this === self::Cancelled;
            }
        }

        PHP;

    $path = $directory.'/app/'.$subPath.'/'.$name.'.php';
    perf_put_file($path, $source);

    return ['name' => $namespace.'\\'.$name, 'path' => $path];
}

/**
 * @return array{name: string, path: string}
 */
function perf_write_concern(string $directory, string $subPath, int $index): array
{
    $name = 'Has'.PERF_NOUNS[$index % count(PERF_NOUNS)].'Trail'.($index >= count(PERF_NOUNS) ? (string) $index : '');
    $namespace = PERF_NAMESPACE.'\\Support\\Concerns';

    $methods = '';
    $methodCount = 3 + ($index % 3);

    for ($m = 0; $m < $methodCount; $m++) {
        $methods .= perf_render_method("traceStep{$m}", 1, 2, 4, $m);
    }

    $source = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        trait {$name}
        {
        {$methods}}

        PHP;

    $path = $directory.'/app/'.$subPath.'/'.$name.'.php';
    perf_put_file($path, $source);

    return ['name' => $namespace.'\\'.$name, 'path' => $path];
}

/**
 * @return array{name: string, path: string}
 */
function perf_write_value_object(string $directory, string $subPath, int $index): array
{
    $name = PERF_NOUNS[$index % count(PERF_NOUNS)].'Details'.($index >= count(PERF_NOUNS) ? (string) $index : '');
    $namespace = PERF_NAMESPACE.'\\ValueObjects';

    // Every sixth value object deliberately carries more than the typical
    // four-parameter limit, so the generated project has real parameter
    // violations to measure and baseline.
    $paramCount = ($index % 6 === 0) ? 6 : 2 + ($index % 3);

    $params = [];
    $assigns = '';

    for ($p = 0; $p < $paramCount; $p++) {
        $type = match ($p % 3) {
            0 => 'string',
            1 => 'int',
            default => '?string',
        };
        $params[] = "public readonly {$type} \$field{$p}";
    }

    $paramList = implode(",\n        ", $params);

    $source = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        final readonly class {$name}
        {
            public function __construct(
                {$paramList},
            ) {
            }

            /**
             * @return array<string, string|int|null>
             */
            public function toArray(): array
            {
                return get_object_vars(\$this);
            }

            public function summary(): string
            {
                \$parts = array_map(
                    static fn (mixed \$value): string => \$value === null ? '-' : (string) \$value,
                    array_values(\$this->toArray()),
                );

                return implode(':', \$parts);
            }
        }

        PHP;

    $path = $directory.'/app/'.$subPath.'/'.$name.'.php';
    perf_put_file($path, $source);

    return ['name' => $namespace.'\\'.$name, 'path' => $path];
}

/**
 * @param array{enums: list<array{name: string, path: string}>, concerns: list<array{name: string, path: string}>, valueObjects: list<array{name: string, path: string}>} $refs
 * @param array{methodsRange: array{0: int, 1: int}, bodyLinesRange: array{0: int, 1: int}} $profile
 * @param array{methodIndex: int, files: int, lines: int} $state
 */
function perf_write_kind_class(string $directory, string $kindDir, string $name, array $refs, array $profile, array &$state): string
{
    $namespace = PERF_NAMESPACE.'\\'.$kindDir;

    $concern = $refs['concerns'][array_sum(array_map('ord', str_split($name))) % count($refs['concerns'])];
    $valueObject = $refs['valueObjects'][array_sum(array_map('ord', str_split($name))) % count($refs['valueObjects'])];
    $enum = $refs['enums'][strlen($name) % count($refs['enums'])];

    $valueObjectShort = perf_short_name($valueObject['name']);
    $enumShort = perf_short_name($enum['name']);
    $concernShort = perf_short_name($concern['name']);

    [$min, $max] = $profile['methodsRange'];
    $methodCount = $min + (mt_rand(0, $max - $min));

    $methods = '';

    for ($m = 0; $m < $methodCount; $m++) {
        $methods .= perf_next_method($state, $m, $profile, $valueObjectShort, $enumShort);
    }

    $source = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use {$valueObject['name']};
        use {$enum['name']};
        use {$concern['name']};

        final class {$name}
        {
            use {$concernShort};

            private ?{$valueObjectShort} \$profile = null;

            private {$enumShort} \$status = {$enumShort}::Pending;

        {$methods}}

        PHP;

    $path = $directory.'/app/'.$kindDir.'/'.$name.'.php';
    perf_put_file($path, $source);

    return $path;
}

function perf_short_name(string $fqcn): string
{
    $parts = explode('\\', $fqcn);

    return $parts[count($parts) - 1];
}

/**
 * @param array{methodIndex: int, files: int, lines: int} $state
 * @param array{bodyLinesRange: array{0: int, 1: int}} $profile
 */
function perf_next_method(array &$state, int $localIndex, array $profile, string $valueObjectShort, string $enumShort): string
{
    $globalIndex = $state['methodIndex']++;
    $name = 'handleStep'.$localIndex;

    // Every twentieth method deliberately breaks one limit, rotating
    // through complexity, line count and parameter count so the project
    // has real, varied violations for the baseline scenario to accept.
    if ($globalIndex % 20 === 0) {
        return match (intdiv($globalIndex, 20) % 3) {
            0 => perf_render_overcomplex_method($name),
            1 => perf_render_overlong_method($name),
            default => perf_render_overparam_method($name),
        };
    }

    [$minLines, $maxLines] = $profile['bodyLinesRange'];
    $lines = mt_rand($minLines, $maxLines);
    $complexity = 1 + mt_rand(0, 3);
    $params = mt_rand(0, 3);

    return perf_render_method($name, $params, $complexity, $lines, $globalIndex, $valueObjectShort, $enumShort);
}

function perf_render_method(
    string $name,
    int $paramCount,
    int $complexity,
    int $targetLines,
    int $variant,
    string $valueObjectShort = 'string',
    string $enumShort = 'string',
): string {
    $params = [];

    for ($p = 0; $p < $paramCount; $p++) {
        $params[] = match ($p % 3) {
            0 => "int \$value{$p}",
            1 => "string \$label{$p}",
            default => "?string \$note{$p} = null",
        };
    }

    $paramList = implode(', ', $params);
    $branches = max(0, $complexity - 1);
    $body = "        \$value = 0;\n";

    for ($b = 0; $b < $branches; $b++) {
        $body .= "        if (\$value === {$b}) {\n            \$value++;\n        }\n";
    }

    $filler = max(0, $targetLines - ($branches * 3) - 2);

    for ($f = 0; $f < $filler; $f++) {
        $body .= "        \$value += ".($f + 1).";\n";
    }

    $body .= match ($variant % 4) {
        0 => "        \$label = \$this->status->label();\n\n        return \$label !== '' ? \$value : 0;\n",
        1 => "        \$note = \$this->profile?->summary() ?? 'unknown';\n\n        return strlen(\$note) + \$value;\n",
        2 => "        \$doubled = array_map(static fn (int \$item): int => \$item * 2, [\$value, 1, 2]);\n\n        return array_sum(\$doubled);\n",
        default => "        return match (true) {\n            \$value > 10 => \$value,\n            \$value > 0 => \$value + 1,\n            default => 0,\n        };\n",
    };

    return <<<PHP

            public function {$name}({$paramList}): int
            {
        {$body}    }

        PHP;
}

/**
 * Twelve branches over one plus/minus one pattern pushes `ccn2` to 13,
 * above the ten-method-complexity limit the generated project checks,
 * while its body stays short enough to respect the lines limit.
 */
function perf_render_overcomplex_method(string $name): string
{
    $body = "        \$value = 0;\n";

    for ($b = 0; $b < 12; $b++) {
        $body .= "        if (\$value === {$b}) {\n            \$value++;\n        }\n";
    }

    $body .= "\n        return \$value;\n";

    return <<<PHP

            public function {$name}(int \$value = 0): int
            {
        {$body}    }

        PHP;
}

/**
 * Twenty-five linear statements with no branching push `lines` past the
 * twenty-line limit while `ccn2` stays at its minimum of one.
 */
function perf_render_overlong_method(string $name): string
{
    $body = '';

    for ($i = 0; $i < 24; $i++) {
        $body .= "        \$value += ".($i + 1).";\n";
    }

    $body .= "\n        return \$value;\n";

    return <<<PHP

            public function {$name}(int \$value = 0): int
            {
        {$body}    }

        PHP;
}

/**
 * Six parameters push the method's parameter count past the four-parameter
 * limit, with a body trivial enough to respect the other two.
 */
function perf_render_overparam_method(string $name): string
{
    return <<<PHP

            public function {$name}(
                int \$a,
                int \$b,
                int \$c,
                string \$d,
                string \$e,
                ?string \$f = null,
            ): int {
                return \$a + \$b + \$c;
            }

        PHP;
}

function perf_put_file(string $path, string $contents): void
{
    $directory = dirname($path);

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($path, $contents);
}

function perf_write_composer_json(string $directory): void
{
    $packageRoot = dirname(__DIR__, 2);

    $composer = [
        'name' => 'rdgs/pest-plugin-quality-perf-project',
        'description' => 'Generated throwaway project used to benchmark pest-plugin-code-quality.',
        'type' => 'project',
        'repositories' => [
            [
                'type' => 'path',
                'url' => $packageRoot,
                'options' => ['symlink' => false],
            ],
        ],
        'require' => ['php' => '^8.4'],
        'require-dev' => [
            'ianrodrigues/pest-plugin-code-quality' => '*',
            'pestphp/pest' => '^5.0',
            'pestphp/pest-plugin-arch' => '~5.0.0',
        ],
        'autoload' => [
            'psr-4' => ['PerfProject\\App\\' => 'app/'],
        ],
        'config' => [
            'allow-plugins' => ['pestphp/pest-plugin' => true],
            'sort-packages' => true,
        ],
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
    ];

    perf_put_file(
        $directory.'/composer.json',
        (string) json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
    );
}

function perf_write_phpunit_xml(string $directory): void
{
    $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
                 bootstrap="vendor/autoload.php"
                 colors="true"
        >
            <testsuites>
                <testsuite name="Test Suite">
                    <directory suffix="Test.php">./tests</directory>
                </testsuite>
            </testsuites>
            <source>
                <include>
                    <directory>app</directory>
                </include>
            </source>
        </phpunit>

        XML;

    perf_put_file($directory.'/phpunit.xml', $xml);
}

/**
 * `Config::baseline()` only activates when `PERF_BASELINE_PATH` is set, so
 * the same generated project serves both the cold and baseline-configured
 * benchmark scenarios.
 */
function perf_write_pest_bootstrap(string $directory): void
{
    $php = <<<'PHP'
        <?php

        declare(strict_types=1);

        $baselinePath = getenv('PERF_BASELINE_PATH');

        if (is_string($baselinePath) && $baselinePath !== '') {
            \IanRodrigues\CodeQuality\Config::baseline($baselinePath);
        }

        PHP;

    perf_put_file($directory.'/tests/Pest.php', $php);
}

function perf_write_quality_test(string $directory): void
{
    $php = <<<PHP
        <?php

        declare(strict_types=1);

        arch('perf project methods stay within complexity limits')
            ->expect('PerfProject\App')
            ->classes()
            ->toHaveMethodComplexityAtMost(10);

        arch('perf project methods stay within line limits')
            ->expect('PerfProject\App')
            ->classes()
            ->toHaveMethodLinesAtMost(20);

        arch('perf project methods stay within parameter limits')
            ->expect('PerfProject\App')
            ->classes()
            ->toHaveMethodParametersAtMost(4);

        PHP;

    perf_put_file($directory.'/tests/Architecture/QualityTest.php', $php);
}

/**
 * Only runs when this file is executed directly, e.g.
 * `php tests/Performance/generate-project.php /tmp/perf-project --profile=small`;
 * `require`d by `bench.php` and the double-parse guard test, it only ever
 * defines these functions.
 */
if (isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $directory = $argv[1] ?? null;

    if (! is_string($directory) || $directory === '') {
        fwrite(STDERR, "Usage: php generate-project.php <target-directory> [--seed=42] [--profile=full|small]\n");

        exit(1);
    }

    $seed = 42;
    $profile = PERF_PROFILE_FULL;

    foreach (array_slice($argv, 2) as $argument) {
        if (str_starts_with($argument, '--seed=')) {
            $seed = (int) substr($argument, strlen('--seed='));
        }

        if ($argument === '--profile=small') {
            $profile = PERF_PROFILE_SMALL;
        }
    }

    $stats = perf_generate_project($directory, $seed, $profile);

    fwrite(STDOUT, sprintf("Generated %d files, %d lines, in %s\n", $stats['files'], $stats['lines'], $directory));
}
