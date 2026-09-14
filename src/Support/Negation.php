<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Support;

use Pest\Expectation;
use Pest\Expectations\OppositeExpectation;

final class Negation
{
    /**
     * `OppositeExpectation` is final and never consults the registered
     * expectation extensions: it forwards the call straight back to the
     * original `Expectation`, which then invokes the extension closure.
     * The only signal left is the call stack, where that forwarding frame
     * sits directly behind the `Expectation::__call` that reached us.
     *
     * Matching the pair, rather than looking for an `OppositeExpectation`
     * frame anywhere, keeps an unrelated outer negation — `expect(fn () =>
     * ...)->not->toThrow(...)` around a chain, say — from being read as a
     * negated limit.
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
