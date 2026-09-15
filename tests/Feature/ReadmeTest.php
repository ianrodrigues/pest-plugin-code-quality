<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Tests\Support\ReadmeProject;

/**
 * Runs every executable code sample in README.md against the throwaway
 * `Readme` project. Every scenario lives in this one file so none race the
 * shared project under `--parallel` — same reason as `BaselineCliTest`.
 */
function readme_invoice(int $complexity): void
{
    $body = '';

    for ($branch = 1; $branch < $complexity; $branch++) {
        $body .= "\n        if (\$value === {$branch}) {\n            \$value++;\n        }\n";
    }

    ReadmeProject::write('app/Billing/Invoice.php', implode("\n", [
        '<?php',
        '',
        'declare(strict_types=1);',
        '',
        'namespace App\Billing;',
        '',
        'final class Invoice',
        '{',
        '    public function total(int $value): int',
        '    {'.$body,
        '        return $value;',
        '    }',
        '}',
        '',
    ]));
}

function readme_metric_class(string $class, string $body): void
{
    ReadmeProject::write("app/Support/{$class}.php", implode("\n", [
        '<?php',
        '',
        'declare(strict_types=1);',
        '',
        'namespace App\Support;',
        '',
        "final class {$class}",
        '{',
        '    '.str_replace("\n", "\n    ", $body),
        '}',
        '',
    ]));
}

/**
 * A class-level sample is a whole declaration, so it is written as the
 * file, not wrapped in one the way a method sample is.
 */
function readme_metric_source(string $class, string $body): void
{
    ReadmeProject::write("app/Support/{$class}.php", implode("\n", [
        '<?php',
        '',
        'declare(strict_types=1);',
        '',
        'namespace App\Support;',
        '',
        $body,
        '',
    ]));
}

function readme_metric_policy(string $target, string $method, int $limit): void
{
    ReadmeProject::writeTest(implode("\n", [
        "arch('the worked example stays within its documented value')",
        "    ->expect('{$target}')",
        '    ->classes()',
        "    ->{$method}({$limit});",
    ]));
}

/**
 * The adoption story's policy plus its `Config::baseline()` call, copied
 * verbatim from the README's own snippet.
 */
function readme_adoption_setup(): void
{
    ReadmeProject::writeTest(implode("\n", [
        "arch('invoices stay easy to change')",
        "    ->expect('App\\Billing')",
        '    ->classes()',
        '    ->toHaveMethodComplexityAtMost(10);',
    ]), 'tests/AdoptionTest.php');
    ReadmeProject::writeTest(trim(ReadmeProject::block('adopt-config')), 'tests/Pest.php');
}

beforeEach(function (): void {
    ReadmeProject::reset();
});

afterAll(function (): void {
    ReadmeProject::reset();
});

it('fails on the controller as documented, and passes the parser policy', function (): void {
    ReadmeProject::writeTest(ReadmeProject::block('quick-start-policies'));

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('parsers stay within their complexity budget')
        ->and($result['output'])->toContain(trim(ReadmeProject::block('quick-start-failure')));
});

it('composes a method limit after a built-in arch expectation, exactly as the README shows it', function (): void {
    ReadmeProject::writeTest(ReadmeProject::block('composition-example'));

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])
        ->toContain('controllers behave, and stay small')
        ->toContain('Method complexity (ccn2 v1): 11');
});

it('passes on a namespace with no eligible methods, exactly as the README shows it', function (): void {
    ReadmeProject::writeTest(ReadmeProject::block('allow-empty'));

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->toBe(0);
});

it('errors on that same empty namespace without allowEmpty', function (): void {
    ReadmeProject::writeTest(str_replace(', allowEmpty: true', '', ReadmeProject::block('allow-empty')));

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('EmptySelection');
});

it('generates a baseline from the config snippet, exactly as the README shows it failing and passing', function (): void {
    readme_adoption_setup();
    readme_invoice(14);

    $generated = ReadmeProject::runCommand(trim(ReadmeProject::block('adopt-generate')));

    expect($generated['output'])
        ->toContain('Quality baseline written: tests/quality-baseline.json')
        ->toContain('added 1, removed 0, increased 0, decreased 0')
        ->toContain('App\Billing\Invoice::total (ccn2 14)');

    $passing = ReadmeProject::runPest();

    expect($passing['exitCode'])->toBe(0);
});

it('fails with the documented regression block once the baselined method grows further', function (): void {
    readme_adoption_setup();
    readme_invoice(14);
    ReadmeProject::runCommand(trim(ReadmeProject::block('adopt-generate')));

    readme_invoice(15);
    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain(trim(ReadmeProject::block('adopt-regression-failure')));
});

it('tightens with the documented command and diff once the method improves', function (): void {
    readme_adoption_setup();
    readme_invoice(14);
    ReadmeProject::runCommand(trim(ReadmeProject::block('adopt-generate')));

    readme_invoice(12);
    $tightened = ReadmeProject::runCommand(trim(ReadmeProject::block('adopt-tighten')));

    expect($tightened['exitCode'])->toBe(0)
        ->and($tightened['output'])
        ->toContain('added 0, removed 0, increased 0, decreased 1')
        ->toContain('~ App\Billing\Invoice::total (ccn2 14 -> 12)');
});

it('prints the documented inspection report and leaves the exit code on --quality-inspect', function (): void {
    ReadmeProject::writeTest(ReadmeProject::block('quick-start-policies'));

    $result = ReadmeProject::runCommand(ReadmeProject::block('inspect-command'));

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain(trim(ReadmeProject::block('inspect-output')));
});

it('leaves the exit code on --quality-json, matching a run without it', function (): void {
    ReadmeProject::writeTest(ReadmeProject::block('quick-start-policies'));

    $plain = ReadmeProject::runPest();
    $withJson = ReadmeProject::runCommand(str_replace(
        'path/to/report.json',
        'quality-report.json',
        ReadmeProject::block('json-command'),
    ));

    expect($withJson['exitCode'])->toBe($plain['exitCode'])
        ->and(ReadmeProject::path('quality-report.json'))->toBeFile();
});

it('measures the ccn2 worked example at the tally the README gives it', function (): void {
    readme_metric_class('Classifier', ReadmeProject::block('ccn2-worked-example'));
    readme_metric_policy('App\Support\Classifier', 'toHaveMethodComplexityAtMost', 5);

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Method complexity (ccn2 v1): 6');
});

it('measures the lines worked example at the count the README gives it', function (): void {
    readme_metric_class('LinesExample', ReadmeProject::block('lines-worked-example'));
    readme_metric_policy('App\Support\LinesExample', 'toHaveMethodLinesAtMost', 2);

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Method lines (lines v1): 3');
});

it('measures the params worked example at the count the README gives it', function (): void {
    readme_metric_class('ParamsExample', ReadmeProject::block('params-worked-example'));
    readme_metric_policy('App\Support\ParamsExample', 'toHaveMethodParametersAtMost', 3);

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Method parameters (params v1): 4');
});

it('measures the methods worked example at the tally the README gives it', function (): void {
    readme_metric_source('MethodsExample', ReadmeProject::block('methods-worked-example'));
    readme_metric_policy('App\Support\MethodsExample', 'toHaveMethodsAtMost', 3);

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Class methods (methods v1): 4');
});

it('drops the accessors of that same example when asked to ignore them', function (): void {
    readme_metric_source('MethodsExample', ReadmeProject::block('methods-worked-example'));
    ReadmeProject::writeTest(implode("\n", [
        "arch('the worked example stays within its documented value')",
        "    ->expect('App\Support\MethodsExample')",
        '    ->classes()',
        '    ->toHaveMethodsAtMost(1, ignoringAccessors: true);',
    ]));

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Class methods (methods v1): 2');
});

it('measures the properties worked example at the count the README gives it', function (): void {
    readme_metric_source('PropertiesExample', ReadmeProject::block('properties-worked-example'));
    readme_metric_policy('App\Support\PropertiesExample', 'toHavePropertiesAtMost', 3);

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Class properties (properties v1): 4');
});

it('measures the inheritance worked example at the depth the README gives it', function (): void {
    readme_metric_source('InheritanceExample', ReadmeProject::block('inheritance-worked-example'));
    readme_metric_policy('App\Support\InheritanceExample', 'toHaveInheritanceDepthAtMost', 1);

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Inheritance depth (inheritance v1): 2');
});

it('measures the class lines worked example at the count the README gives it', function (): void {
    readme_metric_source('ClassLinesExample', ReadmeProject::block('class-lines-worked-example'));
    readme_metric_policy('App\Support\ClassLinesExample', 'toHaveClassLinesAtMost', 2);

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain('Class lines (classLines v1): 3');
});

it('names the class, not a method, when a class-scoped expectation fails', function (): void {
    ReadmeProject::writeTest(implode("\n", [
        "arch('controllers stay small')",
        "    ->expect('App\Http\Controllers')",
        '    ->classes()',
        '    ->toHaveClassLinesAtMost(20);',
    ]));

    $result = ReadmeProject::runPest();

    expect($result['exitCode'])->not->toBe(0)
        ->and($result['output'])->toContain(trim(ReadmeProject::block('class-failure')));
});
