<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Baseline;

/**
 * What a run does with the configured baseline file: honour it, rewrite it
 * from the current measurements, or lower it to them.
 */
enum BaselineMode
{
    case Check;
    case Generate;
    case Tighten;
}
