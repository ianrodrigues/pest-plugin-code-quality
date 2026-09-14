<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

final readonly class BaselineUpdate
{
    public function __construct(
        public Baseline $baseline,
        public BaselineDiff $diff,
    ) {
    }
}
