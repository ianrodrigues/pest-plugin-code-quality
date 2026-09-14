<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Metrics;

/**
 * The identity of a measured metric: its name and definition version.
 *
 * Every measurement is tagged with a `MetricId` so that changing a metric's
 * definition (for example, deciding that `xor` should no longer contribute
 * to `ccn2`) bumps the version instead of silently reinterpreting existing
 * baselines.
 */
final readonly class MetricId
{
    private function __construct(
        public string $name,
        public int $version,
    ) {
    }

    public static function ccn2(int $version = 1): self
    {
        return new self('ccn2', $version);
    }

    public static function lines(int $version = 1): self
    {
        return new self('lines', $version);
    }

    public static function params(int $version = 1): self
    {
        return new self('params', $version);
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name
            && $this->version === $other->version;
    }

    public function __toString(): string
    {
        return "{$this->name}@{$this->version}";
    }
}
