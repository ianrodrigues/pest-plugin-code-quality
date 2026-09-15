<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Reporting\RunRecorder;

beforeEach(function (): void {
    RunRecorder::reset();
});

afterEach(function (): void {
    RunRecorder::reset();
});

it('records a passing policy with its declaration site, targets and measurements', function (): void {
    $line = __LINE__ + 1;
    policy(fn () => expect(FIXTURE_APP.'\Billing')->classes()->toHaveMethodComplexityAtMost(99))();

    $entries = RunRecorder::all();

    expect($entries)->toHaveCount(1);

    $entry = $entries[0];

    expect($entry['id'])->toContain('records a passing policy')
        ->and($entry['location'])->toBe(['file' => 'tests/Feature/RunRecorderTest.php', 'line' => $line])
        ->and($entry['targets'])->toBe([FIXTURE_APP.'\Billing'])
        ->and($entry['metric'])->toBe(['name' => 'ccn2', 'version' => 1])
        ->and($entry['limit'])->toBe(99)
        ->and($entry['directories'])->toBe(['tests/Fixtures/App/Billing'])
        ->and($entry['coverage']['filesFound'])->toBe(1)
        ->and($entry['coverage']['methodsMeasured'])->toBe(1)
        ->and($entry['violations'])->toBeEmpty()
        ->and($entry['measurements'])->toHaveCount(1)
        ->and($entry['measurements'][0])->toBe([
        'symbol' => 'IanRodrigues\CodeQuality\Tests\Fixtures\App\Billing\Invoice::total',
        'path' => 'tests/Fixtures/App/Billing/Invoice.php',
        'line' => 9,
        'ccn2' => 2,
        'lines' => 4,
        'params' => 1,
        'methodName' => 5,
        'variableName' => 5,
    ]);
});

it('records a failing policy including its violations, without aborting the recording', function (): void {
    $line = __LINE__ + 1;
    $chain = policy(fn () => expect(FIXTURE_APP.'\Parsing')->classes()->toHaveMethodComplexityAtMost(5));

    expect($chain)->toThrow(QualityExpectationFailed::class);

    $entries = RunRecorder::all();

    expect($entries)->toHaveCount(1);

    $entry = $entries[0];

    expect($entry['location']['line'])->toBe($line)
        ->and($entry['violations'])->toHaveCount(1)
        ->and($entry['violations'][0]['symbol'])->toBe('IanRodrigues\CodeQuality\Tests\Fixtures\App\Parsing\Parser::parse');
});

it('records exclusions applied through ignoring()', function (): void {
    policy(fn () => expect(FIXTURE_APP.'\Http\Controllers')
        ->classes()
        ->toHaveMethodComplexityAtMost(99)
        ->ignoring(FIXTURE_APP.'\Http\Controllers\Generated'))();

    $entries = RunRecorder::all();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['exclusions'])->toBe([FIXTURE_APP.'\Http\Controllers\Generated']);
});

it('records one entry per policy declared on the same chain, each with its own line', function (): void {
    $firstLine = __LINE__ + 3;
    policy(fn () => expect(FIXTURE_APP.'\Billing')
        ->classes()
        ->toHaveMethodComplexityAtMost(99)
        ->toHaveMethodLinesAtMost(99))();

    $entries = RunRecorder::all();

    expect($entries)->toHaveCount(2)
        ->and($entries[0]['location']['line'])->toBe($firstLine)
        ->and($entries[1]['location']['line'])->toBe($firstLine + 1);
});

it('records the declaration site correctly when composed with a built-in expectation first', function (): void {
    $line = __LINE__ + 4;
    policy(fn () => expect(FIXTURE_APP.'\Billing')
        ->classes()
        ->toBeFinal()
        ->toHaveMethodComplexityAtMost(99))();

    $entries = RunRecorder::all();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]['location'])->toBe(['file' => 'tests/Feature/RunRecorderTest.php', 'line' => $line]);
});
