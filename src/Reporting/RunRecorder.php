<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Reporting;

use IanRodrigues\CodeQuality\Analysis\MethodMeasurements;
use IanRodrigues\CodeQuality\Baseline\BaselineEntry;
use IanRodrigues\CodeQuality\Baseline\DuplicatePolicyIdentity;
use IanRodrigues\CodeQuality\Baseline\PolicyBaseline;
use IanRodrigues\CodeQuality\Policies\Policy;
use IanRodrigues\CodeQuality\Policies\PolicyResult;
use IanRodrigues\CodeQuality\Policies\Violation;
use IanRodrigues\CodeQuality\Selection\Coverage;
use IanRodrigues\CodeQuality\Selection\SkippedFile;
use IanRodrigues\CodeQuality\Support\ProjectPath;

/**
 * Every policy run seen across the whole process, kept as plain arrays
 * rather than the richer domain objects so memory stays bounded and a
 * worker process can serialise its share straight to JSON.
 *
 * @phpstan-import-type BaselineEntryRow from BaselineEntry
 * @phpstan-type PolicyEntry array{
 *     id: string,
 *     policy: string,
 *     location: array{file: string, line: int},
 *     targets: list<string>,
 *     metric: array{name: string, version: int},
 *     limit: int,
 *     directories: list<string>,
 *     exclusions: list<string>,
 *     coverage: array{
 *         filesFound: int,
 *         objects: int,
 *         withAst: int,
 *         methodsMeasured: int,
 *         skipped: list<array{path: string, reason: string}>,
 *         withoutClasses?: int,
 *     },
 *     baseline: array{
 *         path: string,
 *         applied: int,
 *         accepted: array<string, int>,
 *         stale: list<BaselineEntryRow>,
 *     }|null,
 *     measurements: list<array{symbol: string, path: string, line: int, ccn2: int|null, lines: int|null, params: int}>,
 *     violations: list<array{symbol: string, path: string, line: int, value: int, limit: int}>,
 *     errors: list<string>,
 * }
 */
final class RunRecorder
{
    /** @var list<PolicyEntry> */
    private static array $entries = [];

    private function __construct()
    {
    }

    /**
     * @param list<string> $targets
     * @param list<string> $exclusions
     */
    public static function record(PolicyResult $result, PolicyLocation $location, array $targets, array $exclusions): void
    {
        self::$entries[] = [
            'id' => TestIdentity::current(),
            'policy' => $result->identity,
            'location' => ['file' => $location->file, 'line' => $location->line],
            'targets' => $targets,
            'metric' => ['name' => $result->policy->metric->value, 'version' => $result->policy->metric->version()],
            'limit' => $result->policy->limit,
            'directories' => self::directoriesOf($result->coverage),
            'exclusions' => $exclusions,
            'coverage' => self::coverageOf($result),
            'baseline' => self::baselineOf($result),
            'measurements' => array_map(self::measurementRow(...), $result->measurements),
            'violations' => array_map(self::violationRow(...), $result->violations),
            'errors' => [],
        ];
    }

    /**
     * A policy that never produced a result still has to leave a trace:
     * generating or tightening a baseline off a run with a hole in it would
     * write allowances for code nobody measured.
     *
     * @param list<string> $targets
     */
    public static function recordError(
        string $identity,
        Policy $policy,
        PolicyLocation $location,
        array $targets,
        string $message,
    ): void {
        self::$entries[] = [
            'id' => TestIdentity::current(),
            'policy' => $identity,
            'location' => ['file' => $location->file, 'line' => $location->line],
            'targets' => $targets,
            'metric' => ['name' => $policy->metric->value, 'version' => $policy->metric->version()],
            'limit' => $policy->limit,
            'directories' => [],
            'exclusions' => [],
            'coverage' => ['filesFound' => 0, 'objects' => 0, 'withAst' => 0, 'methodsMeasured' => 0, 'skipped' => []],
            'baseline' => null,
            'measurements' => [],
            'violations' => [],
            'errors' => [$message],
        ];
    }

    public static function ensureUnique(string $identity, PolicyLocation $location): void
    {
        foreach (self::$entries as $entry) {
            if ($entry['policy'] !== $identity) {
                continue;
            }

            throw DuplicatePolicyIdentity::between(
                $identity,
                "{$entry['location']['file']}:{$entry['location']['line']}",
                "{$location->file}:{$location->line}",
            );
        }
    }

    /**
     * @return list<PolicyEntry>
     */
    public static function all(): array
    {
        return self::$entries;
    }

    /**
     * @param list<PolicyEntry> $entries
     */
    public static function merge(array $entries): void
    {
        self::$entries = [...self::$entries, ...$entries];
    }

    public static function reset(): void
    {
        self::$entries = [];
    }

    /**
     * @return array{filesFound: int, objects: int, withAst: int, methodsMeasured: int, skipped: list<array{path: string, reason: string}>, withoutClasses?: int}
     */
    private static function coverageOf(PolicyResult $result): array
    {
        $coverage = $result->coverage;

        return [
            ...self::coverageCounts($coverage),
            'methodsMeasured' => $result->methodsMeasured,
            'skipped' => self::skippedRows($coverage),
            ...self::withoutClassesField($coverage),
        ];
    }

    /**
     * @return array{filesFound: int, objects: int, withAst: int}
     */
    private static function coverageCounts(?Coverage $coverage): array
    {
        return [
            'filesFound' => $coverage?->filesFound() ?? 0,
            'objects' => $coverage?->objectsProduced() ?? 0,
            'withAst' => $coverage?->objectsWithAst() ?? 0,
        ];
    }

    /**
     * @return list<array{path: string, reason: string}>
     */
    private static function skippedRows(?Coverage $coverage): array
    {
        return array_map(
            static fn (SkippedFile $file): array => ['path' => $file->path, 'reason' => $file->reason->value],
            $coverage?->skippedFiles() ?? [],
        );
    }

    /**
     * Present only when files without a class-like symbol were found:
     * the rest of the coverage block always carries its keys, but this
     * one is additive so an older `quality-report.v1.json` consumer
     * ignores it safely.
     *
     * @return array{withoutClasses: int}|array{}
     */
    private static function withoutClassesField(?Coverage $coverage): array
    {
        $count = $coverage?->withoutClasses() ?? 0;

        return $count > 0 ? ['withoutClasses' => $count] : [];
    }

    /**
     * Only the entries that reached a method this run actually measured
     * count as applied; the rest describe symbols that are gone.
     *
     * @return array{path: string, applied: int, accepted: array<string, int>, stale: list<BaselineEntryRow>}|null
     */
    private static function baselineOf(PolicyResult $result): ?array
    {
        $baseline = $result->baseline;

        if (!$baseline instanceof PolicyBaseline) {
            return null;
        }

        $accepted = [];

        foreach ($result->measurements as $method) {
            $value = $baseline->acceptedFor($method->symbol);

            if ($value !== null) {
                $accepted[$method->symbol] = $value;
            }
        }

        return [
            'path' => ProjectPath::relative($baseline->path),
            'applied' => count($accepted),
            'accepted' => $accepted,
            'stale' => array_map(static fn (BaselineEntry $entry): array => $entry->toArray(), $baseline->stale),
        ];
    }

    /**
     * @return list<string>
     */
    private static function directoriesOf(?Coverage $coverage): array
    {
        if (! $coverage instanceof Coverage) {
            return [];
        }

        $directories = [];

        foreach ($coverage->targets as $target) {
            foreach ($target->directories as $directory) {
                $directories[$directory] = true;
            }
        }

        return array_keys($directories);
    }

    /**
     * @return array{symbol: string, path: string, line: int, ccn2: int|null, lines: int|null, params: int}
     */
    private static function measurementRow(MethodMeasurements $method): array
    {
        return [
            'symbol' => $method->symbol,
            'path' => ProjectPath::relative($method->path),
            'line' => $method->line,
            'ccn2' => $method->ccn2,
            'lines' => $method->lines,
            'params' => $method->params,
        ];
    }

    /**
     * @return array{symbol: string, path: string, line: int, value: int, limit: int}
     */
    private static function violationRow(Violation $violation): array
    {
        return [
            'symbol' => $violation->symbol,
            'path' => $violation->path,
            'line' => $violation->line,
            'value' => $violation->value,
            'limit' => $violation->limit,
        ];
    }
}
