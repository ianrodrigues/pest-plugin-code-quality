<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

/**
 * What writing a baseline changed, in the terms someone reviewing the
 * change cares about: allowances gained, dropped, raised or lowered.
 */
final readonly class BaselineDiff
{
    /**
     * @param list<string> $added
     * @param list<string> $removed
     * @param list<string> $increased
     * @param list<string> $decreased
     */
    private function __construct(
        public array $added,
        public array $removed,
        public array $increased,
        public array $decreased,
    ) {
    }

    public static function between(Baseline $before, Baseline $after): self
    {
        $old = self::keyed($before);
        $new = self::keyed($after);

        $added = [];
        $removed = [];
        $increased = [];
        $decreased = [];

        foreach ($new as $key => $entry) {
            if (! isset($old[$key])) {
                $added[] = sprintf('+ %s (%s %d)', $entry->symbol, $entry->metric->value, $entry->accepted);

                continue;
            }

            $previous = $old[$key]->accepted;

            if ($entry->accepted > $previous) {
                $increased[] = self::change($entry, $previous);
            } elseif ($entry->accepted < $previous) {
                $decreased[] = self::change($entry, $previous);
            }
        }

        foreach ($old as $key => $entry) {
            if (! isset($new[$key])) {
                $removed[] = sprintf('- %s (%s)', $entry->symbol, $entry->metric->value);
            }
        }

        return new self($added, $removed, $increased, $decreased);
    }

    public function summary(): string
    {
        return sprintf(
            'added %d, removed %d, increased %d, decreased %d',
            count($this->added),
            count($this->removed),
            count($this->increased),
            count($this->decreased),
        );
    }

    /**
     * @return list<string>
     */
    public function lines(): array
    {
        return [...$this->added, ...$this->removed, ...$this->increased, ...$this->decreased];
    }

    private static function change(BaselineEntry $entry, int $previous): string
    {
        return sprintf('~ %s (%s %d -> %d)', $entry->symbol, $entry->metric->value, $previous, $entry->accepted);
    }

    /**
     * @return array<string, BaselineEntry>
     */
    private static function keyed(Baseline $baseline): array
    {
        $keyed = [];

        foreach ($baseline->entries as $entry) {
            $keyed[$entry->policy."\0".$entry->symbol] = $entry;
        }

        return $keyed;
    }
}
