<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Support;

use Pest\Expectations\OppositeExpectation;

final class Negation
{
    /**
     * `OppositeExpectation` is final and never consults the registered
     * expectation extensions: it forwards the call straight back to the
     * original `Expectation`, which then invokes the extension closure. The
     * only signal left is the call stack, where the forwarding frame is
     * still open while the closure runs, and only then.
     */
    public static function isActive(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8) as $frame) {
            if (($frame['class'] ?? null) === OppositeExpectation::class && $frame['function'] === '__call') {
                return true;
            }
        }

        return false;
    }
}
