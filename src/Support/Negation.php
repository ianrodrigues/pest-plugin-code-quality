<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Support;

use Pest\Expectation;
use Pest\Expectations\OppositeExpectation;

final class Negation
{
    /**
     * `OppositeExpectation` forwards to `Expectation` without consulting
     * extensions, so the call stack is the only signal; matching the exact
     * frame pair avoids misreading an unrelated outer negation as this one.
     */
    public static function isActive(): bool
    {
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($frames as $index => $frame) {
            if (($frame['class'] ?? null) !== Expectation::class || $frame['function'] !== '__call') {
                continue;
            }

            $caller = $frames[$index + 1] ?? null;

            return $caller !== null
                && ($caller['class'] ?? null) === OppositeExpectation::class
                && $caller['function'] === '__call';
        }

        return false;
    }
}
