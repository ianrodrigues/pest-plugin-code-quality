<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Reporting;

use IanRodrigues\CodeQuality\Baseline\BaselineEntry;
use IanRodrigues\CodeQuality\Metrics\Metric;
use IanRodrigues\CodeQuality\Metrics\Scope;

use function Pest\version;

/**
 * Turns the process-wide run log into the two shapes `--quality-inspect`
 * and `--quality-json` can produce: a printable console report, or a
 * versioned JSON document validated against `schema/quality-report.v1.json`.
 *
 * @phpstan-import-type PolicyEntry from RunRecorder
 * @phpstan-import-type CoverageRow from RunRecorder
 * @phpstan-import-type MeasurementRow from RunRecorder
 * @phpstan-import-type BaselineEntryRow from BaselineEntry
 * @phpstan-type BaselineDocument array{path: string, applied: int, stale: list<BaselineEntryRow>}
 * @phpstan-type FindingsPolicyDocument array{
 *     id: string,
 *     location: array{file: string, line: int},
 *     targets: list<string>,
 *     metric: array{name: string, version: int},
 *     limit: int,
 *     coverage: CoverageRow,
 *     baseline?: BaselineDocument,
 *     violations: list<array{symbol: string, path: string, line: int, value: int, limit: int}>,
 *     errors: list<string>,
 * }
 * @phpstan-type FullPolicyDocument array{
 *     id: string,
 *     location: array{file: string, line: int},
 *     targets: list<string>,
 *     metric: array{name: string, version: int},
 *     limit: int,
 *     coverage: CoverageRow,
 *     baseline?: BaselineDocument,
 *     measurements: list<MeasurementRow>,
 *     violations: list<array{symbol: string, path: string, line: int, value: int, limit: int}>,
 *     errors: list<string>,
 * }
 * @phpstan-type FullReportDocument array{
 *     schemaVersion: int,
 *     generatedAt: string,
 *     versions: array{php: string, pest: string, plugin: string},
 *     policies: list<FullPolicyDocument>,
 *     truncated: false,
 * }
 * @phpstan-type FindingsReportDocument array{
 *     schemaVersion: int,
 *     generatedAt: string,
 *     versions: array{php: string, pest: string, plugin: string},
 *     policies: list<FindingsPolicyDocument>,
 *     truncated: false,
 * }
 */
final readonly class QualityReport
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param list<PolicyEntry> $policies sorted by declaration site, then metric
     */
    private function __construct(
        private array $policies,
        private string $generatedAt,
    ) {
    }

    /**
     * @param list<PolicyEntry> $entries
     */
    public static function from(array $entries): self
    {
        $policies = $entries;

        usort($policies, static fn (array $a, array $b): int => [
            $a['location']['file'], $a['location']['line'], $a['metric']['name'],
        ] <=> [
            $b['location']['file'], $b['location']['line'], $b['metric']['name'],
        ]);

        return new self($policies, gmdate('Y-m-d\TH:i:s\Z'));
    }

    /**
     * @return FullReportDocument
     */
    public function full(): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'generatedAt' => $this->generatedAt,
            'versions' => $this->versions(),
            'policies' => array_map($this->fullPolicyDocument(...), $this->policies),
            'truncated' => false,
        ];
    }

    /**
     * Without the per-method measurements that make the full report much larger.
     *
     * @return FindingsReportDocument
     */
    public function findingsOnly(): array
    {
        return [
            'schemaVersion' => self::SCHEMA_VERSION,
            'generatedAt' => $this->generatedAt,
            'versions' => $this->versions(),
            'policies' => array_map($this->findingsPolicyDocument(...), $this->policies),
            'truncated' => false,
        ];
    }

    public function toConsole(): string
    {
        if ($this->policies === []) {
            return 'Quality inspection: no policy ran.';
        }

        $blocks = array_map($this->consoleBlock(...), $this->policies);

        return implode("\n\n", ['Quality inspection', ...$blocks]);
    }

    /**
     * @return array{php: string, pest: string, plugin: string}
     */
    private function versions(): array
    {
        return [
            'php' => PHP_VERSION,
            'pest' => version(),
            'plugin' => \IanRodrigues\CodeQuality\VERSION,
        ];
    }

    /**
     * @param PolicyEntry $policy
     * @return FullPolicyDocument
     */
    private function fullPolicyDocument(array $policy): array
    {
        return [
            ...$this->sharedPolicyFields($policy),
            'measurements' => $this->sortedRows($policy['measurements']),
        ];
    }

    /**
     * @param PolicyEntry $policy
     * @return FindingsPolicyDocument
     */
    private function findingsPolicyDocument(array $policy): array
    {
        return $this->sharedPolicyFields($policy);
    }

    /**
     * @param PolicyEntry $policy
     * @return FindingsPolicyDocument
     */
    private function sharedPolicyFields(array $policy): array
    {
        $baseline = $policy['baseline'];

        return [
            'id' => $policy['id'],
            'location' => $policy['location'],
            'targets' => $policy['targets'],
            'metric' => $policy['metric'],
            'limit' => $policy['limit'],
            'coverage' => $policy['coverage'],
            ...($baseline === null ? [] : ['baseline' => [
                'path' => $baseline['path'],
                'applied' => $baseline['applied'],
                'stale' => $baseline['stale'],
            ]]),
            'violations' => $this->sortedRows($policy['violations']),
            'errors' => $policy['errors'],
        ];
    }

    /**
     * Sorted by path then symbol, matching `PolicyResult::sortedViolations()`.
     *
     * @template TRow of array{symbol: string, path: string}
     * @param list<TRow> $rows
     * @return list<TRow>
     */
    private function sortedRows(array $rows): array
    {
        usort($rows, static fn (array $a, array $b): int => [$a['path'], $a['symbol']] <=> [$b['path'], $b['symbol']]);

        return $rows;
    }

    /**
     * @param PolicyEntry $policy
     */
    private function consoleBlock(array $policy): string
    {
        $scope = Metric::from($policy['metric']['name'])->scope();

        $lines = [
            $policy['id'],
            "{$policy['location']['file']}:{$policy['location']['line']}",
            '',
            'Metric: '.$policy['metric']['name'].' v'.$policy['metric']['version'].', limit '.$policy['limit'],
            'Targets: '.$this->joinOrNone($policy['targets']),
            'Directories searched: '.$this->joinOrNone($policy['directories']),
            'Exclusions: '.$this->joinOrNone($policy['exclusions']),
            sprintf(
                'Files found: %d  Objects: %d  With AST: %d  %s measured: %d',
                $policy['coverage']['filesFound'],
                $policy['coverage']['objects'],
                $policy['coverage']['withAst'],
                ucfirst($scope->plural()),
                $scope === Scope::Method ? $policy['coverage']['methodsMeasured'] : $policy['coverage']['classesMeasured'],
            ),
            ...$this->withoutClassesLines($policy),
        ];

        foreach ($this->baselineLines($policy) as $line) {
            $lines[] = $line;
        }

        if ($policy['coverage']['skipped'] !== []) {
            $lines[] = 'Skipped files:';

            foreach ($policy['coverage']['skipped'] as $skipped) {
                $lines[] = "  {$skipped['path']} ({$skipped['reason']})";
            }
        }

        $measurements = $this->sortedRows($policy['measurements']);

        if ($measurements !== []) {
            $lines[] = 'Measurements:';

            foreach ($measurements as $measurement) {
                $lines[] = $this->measurementLine(
                    $measurement,
                    $scope,
                    $policy['baseline']['accepted'][$measurement['symbol']] ?? null,
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param MeasurementRow $measurement
     */
    private function measurementLine(array $measurement, Scope $scope, ?int $accepted): string
    {
        $values = match ($scope) {
            Scope::Method => $this->methodValues($measurement),
            Scope::ClassLike => $this->classValues($measurement),
        };

        return sprintf(
            '  %s (%s:%d) %s%s',
            $measurement['symbol'],
            $measurement['path'],
            $measurement['line'],
            $values,
            $accepted === null ? '' : " accepted={$accepted}",
        );
    }

    /**
     * @param MeasurementRow $measurement
     */
    private function methodValues(array $measurement): string
    {
        return sprintf(
            'ccn2=%s lines=%s params=%d',
            $this->orNotApplicable($measurement['ccn2'] ?? null),
            $this->orNotApplicable($measurement['lines'] ?? null),
            $measurement['params'] ?? 0,
        );
    }

    /**
     * @param MeasurementRow $measurement
     */
    private function classValues(array $measurement): string
    {
        return sprintf(
            'methods=%d properties=%d inheritance=%s classLines=%d',
            $measurement['methods'] ?? 0,
            $measurement['properties'] ?? 0,
            $this->orNotApplicable($measurement['inheritance'] ?? null),
            $measurement['classLines'] ?? 0,
        );
    }

    private function orNotApplicable(?int $value): string
    {
        return $value === null ? 'n/a' : (string) $value;
    }

    /**
     * @param PolicyEntry $policy
     * @return list<string>
     */
    private function withoutClassesLines(array $policy): array
    {
        $count = $policy['coverage']['withoutClasses'] ?? 0;

        return $count > 0 ? ["Files without classes: {$count}"] : [];
    }

    /**
     * @param PolicyEntry $policy
     * @return list<string>
     */
    private function baselineLines(array $policy): array
    {
        $baseline = $policy['baseline'];

        if ($baseline === null) {
            return [];
        }

        $lines = [sprintf(
            'Baseline: %s (%d accepted, %d stale)',
            $baseline['path'],
            $baseline['applied'],
            count($baseline['stale']),
        )];

        foreach ($baseline['stale'] as $entry) {
            $lines[] = sprintf(
                '  stale: %s (%s v%d, stored for limit %d, accepted %d)',
                $entry['symbol'],
                $entry['metric']['name'],
                $entry['metric']['version'],
                $entry['limit'],
                $entry['accepted'],
            );
        }

        return $lines;
    }

    /**
     * @param list<string> $values
     */
    private function joinOrNone(array $values): string
    {
        return $values === [] ? 'none' : implode(', ', $values);
    }
}
