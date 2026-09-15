<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

use IanRodrigues\CodeQuality\Metrics\Metric;

/**
 * One accepted excess. `$version` and `$limit` are captured at
 * generation time, apart from their current values, so a change
 * marks the entry stale instead of silently reinterpreting it.
 *
 * @phpstan-type BaselineEntryRow array{
 *     policy: string,
 *     symbol: string,
 *     metric: array{name: string, version: int},
 *     limit: int,
 *     accepted: int,
 *     path: string,
 * }
 */
final readonly class BaselineEntry
{
    public function __construct(
        public string $policy,
        public string $symbol,
        public Metric $metric,
        public int $version,
        public int $limit,
        public int $accepted,
        public string $path,
    ) {
    }

    public function appliesTo(Metric $metric, int $limit): bool
    {
        return $this->metric === $metric && $this->version === $metric->version() && $this->limit === $limit;
    }

    public function with(int $accepted): self
    {
        return new self($this->policy, $this->symbol, $this->metric, $this->version, $this->limit, $accepted, $this->path);
    }

    /**
     * @return BaselineEntryRow
     */
    public function toArray(): array
    {
        return [
            'policy' => $this->policy,
            'symbol' => $this->symbol,
            'metric' => ['name' => $this->metric->value, 'version' => $this->version],
            'limit' => $this->limit,
            'accepted' => $this->accepted,
            'path' => $this->path,
        ];
    }

    /**
     * @param BaselineEntryRow $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            $row['policy'],
            $row['symbol'],
            Metric::from($row['metric']['name']),
            $row['metric']['version'],
            $row['limit'],
            $row['accepted'],
            $row['path'],
        );
    }
}
