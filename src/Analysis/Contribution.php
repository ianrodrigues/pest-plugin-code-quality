<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Analysis;

final readonly class Contribution
{
    public function __construct(
        public string $label,
        public ?int $line,
    ) {
    }

    /**
     * @return array{label: string, line: int|null}
     */
    public function toArray(): array
    {
        return ['label' => $this->label, 'line' => $this->line];
    }
}
