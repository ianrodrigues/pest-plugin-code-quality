<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Baseline\Baseline;
use IanRodrigues\CodeQuality\Baseline\BaselineEntry;
use IanRodrigues\CodeQuality\Baseline\BaselineError;
use IanRodrigues\CodeQuality\Baseline\BaselineFile;
use IanRodrigues\CodeQuality\Metrics\Metric;
use JsonSchema\Validator;

function baseline_schema(): object
{
    /** @var object $schema */
    $schema = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/schema/quality-baseline.v1.json'));

    return $schema;
}

function baseline_temporary_path(): string
{
    return tempnam(sys_get_temp_dir(), 'quality-baseline').'.json';
}

function baseline_sample(): Baseline
{
    return Baseline::of([
        new BaselineEntry('orders stay small :: ccn2', 'App\Orders\Order::place', Metric::Ccn2, 1, 10, 16, 'app/Orders/Order.php'),
        new BaselineEntry('orders stay small :: lines', 'App\Orders\Order::place', Metric::Lines, 1, 40, 61, 'app/Orders/Order.php'),
    ]);
}

it('writes a document that validates against the shipped schema', function (): void {
    $path = baseline_temporary_path();

    BaselineFile::write($path, baseline_sample());

    $document = json_decode((string) file_get_contents($path));
    $validator = new Validator();
    $validator->validate($document, baseline_schema());

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid');

    unlink($path);
});

it('reads back exactly what it wrote', function (): void {
    $path = baseline_temporary_path();

    BaselineFile::write($path, baseline_sample());

    expect(BaselineFile::read($path)->entries)->toEqual(baseline_sample()->entries);

    unlink($path);
});

it('round-trips a variableName entry, a metric added after the file shape was fixed', function (): void {
    $path = baseline_temporary_path();

    $baseline = Baseline::of([
        new BaselineEntry('orders stay small :: variableName', 'App\Orders\Order::place', Metric::VariableName, 1, 20, 24, 'app/Orders/Order.php'),
    ]);

    BaselineFile::write($path, $baseline);

    $document = json_decode((string) file_get_contents($path));
    $validator = new Validator();
    $validator->validate($document, baseline_schema());

    expect($validator->isValid())->toBeTrue(json_encode($validator->getErrors()) ?: 'invalid')
        ->and(BaselineFile::read($path)->entries)->toEqual($baseline->entries);

    unlink($path);
});

it('sorts entries by policy then symbol, so two runs write the same bytes', function (): void {
    $entries = [
        new BaselineEntry('b :: ccn2', 'App\Z::z', Metric::Ccn2, 1, 10, 11, 'app/Z.php'),
        new BaselineEntry('a :: ccn2', 'App\B::b', Metric::Ccn2, 1, 10, 11, 'app/B.php'),
        new BaselineEntry('a :: ccn2', 'App\A::a', Metric::Ccn2, 1, 10, 11, 'app/A.php'),
    ];

    $sorted = array_map(
        static fn (BaselineEntry $entry): string => $entry->policy.' '.$entry->symbol,
        Baseline::of($entries)->entries,
    );

    expect($sorted)->toBe(['a :: ccn2 App\A::a', 'a :: ccn2 App\B::b', 'b :: ccn2 App\Z::z']);
});

it('errors on a file that does not exist', function (): void {
    expect(fn (): Baseline => BaselineFile::read(sys_get_temp_dir().'/quality-baseline-absent.json'))
        ->toThrow(BaselineError::class);
});

it('errors on a corrupt document', function (): void {
    $path = baseline_temporary_path();
    file_put_contents($path, '{"schemaVersion": 1, "entries": [');

    expect(fn (): Baseline => BaselineFile::read($path))->toThrow(BaselineError::class);

    unlink($path);
});

it('errors on an unknown schema version', function (): void {
    $path = baseline_temporary_path();
    file_put_contents($path, '{"schemaVersion": 99, "generatedAt": "", "pluginVersion": "", "entries": []}');

    expect(fn (): Baseline => BaselineFile::read($path))->toThrow(BaselineError::class, 'expected schemaVersion 1');

    unlink($path);
});

it('errors on an entry missing its metric', function (): void {
    $path = baseline_temporary_path();
    file_put_contents($path, (string) json_encode([
        'schemaVersion' => 1,
        'generatedAt' => '',
        'pluginVersion' => '',
        'entries' => [['policy' => 'p', 'symbol' => 's', 'limit' => 10, 'accepted' => 11, 'path' => 'a.php']],
    ]));

    expect(fn (): Baseline => BaselineFile::read($path))->toThrow(BaselineError::class, 'no valid metric');

    unlink($path);
});

it('leaves the file on disk untouched when the write cannot complete', function (): void {
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'quality-baseline-locked-'.uniqid();
    mkdir($directory);

    $path = $directory.DIRECTORY_SEPARATOR.'quality-baseline.json';
    BaselineFile::write($path, baseline_sample());
    $before = (string) file_get_contents($path);

    chmod($directory, 0555);

    expect(fn () => BaselineFile::write($path, Baseline::empty()))->toThrow(BaselineError::class)
        ->and(file_get_contents($path))->toBe($before);

    chmod($directory, 0755);
    unlink($path);
    rmdir($directory);
});

it('matches entries to a policy by metric and configured limit', function (): void {
    $baseline = Baseline::of([
        new BaselineEntry('p :: ccn2', 'App\A::a', Metric::Ccn2, 1, 10, 16, 'app/A.php'),
        new BaselineEntry('p :: ccn2', 'App\B::b', Metric::Ccn2, 1, 12, 16, 'app/B.php'),
        new BaselineEntry('p :: ccn2', 'App\C::c', Metric::Ccn2, 2, 10, 16, 'app/C.php'),
        new BaselineEntry('other :: ccn2', 'App\D::d', Metric::Ccn2, 1, 10, 16, 'app/D.php'),
    ]);

    $match = $baseline->match('p :: ccn2', Metric::Ccn2, 10);

    expect($match->accepted)->toBe(['App\A::a' => 16])
        ->and($match->acceptedFor('App\A::a'))->toBe(16)
        ->and($match->acceptedFor('App\D::d'))->toBeNull()
        ->and(array_map(static fn (BaselineEntry $entry): string => $entry->symbol, $match->stale))
        ->toBe(['App\B::b', 'App\C::c']);
});

it('keeps the entries of policies that did not run', function (): void {
    $kept = baseline_sample()->exceptPolicies(['orders stay small :: ccn2']);

    expect(array_map(static fn (BaselineEntry $entry): string => $entry->policy, $kept))
        ->toBe(['orders stay small :: lines']);
});
