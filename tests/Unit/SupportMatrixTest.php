<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/**
 * Parses a simple caret PHP version constraint ("^8.4") into its
 * major/minor lower bound. This package only ever declares caret
 * constraints for `php`, so a full semver range parser is unneeded.
 *
 * @return array{major: int, minor: int}
 */
function parseCaretConstraint(string $constraint): array
{
    if (preg_match('/^\^(\d+)\.(\d+)$/', $constraint, $matches) !== 1) {
        throw new RuntimeException("Unsupported php constraint format: {$constraint}");
    }

    return [
        'major' => (int) $matches[1],
        'minor' => (int) $matches[2],
    ];
}

function readComposerPhpConstraint(): string
{
    /** @var array{require: array{php: string}} $composer */
    $composer = json_decode(
        (string) file_get_contents(__DIR__.'/../../composer.json'),
        associative: true,
    );

    return $composer['require']['php'];
}

/**
 * @return array{php: list<string>, os: list<string>}
 */
function readWorkflowMatrix(): array
{
    /** @var array{jobs: array{test: array{strategy: array{matrix: array{php: list<string>, os: list<string>}}}}} $workflow */
    $workflow = Yaml::parseFile(__DIR__.'/../../.github/workflows/tests.yml');

    return $workflow['jobs']['test']['strategy']['matrix'];
}

it('declares php versions in the matrix that all satisfy the composer constraint', function (): void {
    $phpConstraint = readComposerPhpConstraint();
    $bound = parseCaretConstraint($phpConstraint);

    foreach (readWorkflowMatrix()['php'] as $version) {
        if (preg_match('/^(\d+)\.(\d+)$/', $version, $matches) !== 1) {
            throw new RuntimeException("Unexpected php version format in the matrix: {$version}");
        }

        $major = (int) $matches[1];
        $minor = (int) $matches[2];

        expect($major)->toBe($bound['major'], "PHP {$version} is not in the same major line as {$phpConstraint}");
        expect($minor)->toBeGreaterThanOrEqual($bound['minor'], "PHP {$version} is below the constraint's lower bound {$phpConstraint}");
    }
});

it('covers every minor from the constraint lower bound up to the highest matrix version', function (): void {
    $phpConstraint = readComposerPhpConstraint();
    $bound = parseCaretConstraint($phpConstraint);

    $matrixVersions = readWorkflowMatrix()['php'];

    $minors = array_map(
        static fn (string $version): int => (int) explode('.', $version)[1],
        $matrixVersions,
    );

    $highestMinor = $minors === [] ? $bound['minor'] : max($minors);

    for ($minor = $bound['minor']; $minor <= $highestMinor; $minor++) {
        $expectedVersion = "{$bound['major']}.{$minor}";

        expect(in_array($expectedVersion, $matrixVersions, strict: true))->toBeTrue(
            "PHP {$expectedVersion} is covered by the {$phpConstraint} constraint (up to the highest matrix version) but is missing from the CI matrix",
        );
    }
});

it('runs the matrix on both ubuntu and windows', function (): void {
    $os = readWorkflowMatrix()['os'];

    expect($os)->toContain('ubuntu-latest');
    expect($os)->toContain('windows-latest');
});
