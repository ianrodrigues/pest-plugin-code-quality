<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality;

use Pest\Arch\Contracts\ArchExpectation;
use Pest\Expectation;
use Rdgs\PestCodeQuality\Expectations\PolicyExpectation;
use Rdgs\PestCodeQuality\Policies\Policy;

const VERSION = '0.1.0';

function version(): string
{
    return VERSION;
}

/*
 * The `ArchExpectation` return type is load-bearing: it is what makes
 * `Pest\Expectation::__call` hand the result straight back instead of
 * running the value expectation pipeline, so these read as ordinary
 * members of the `arch()` chain.
 */

expect()->extend('toHaveMethodComplexityAtMost', function (int $max): ArchExpectation {
    /** @var Expectation<array<int, string>|string> $this */
    return PolicyExpectation::make($this, Policy::complexity($max));
});

expect()->extend('toHaveMethodLinesAtMost', function (int $max): ArchExpectation {
    /** @var Expectation<array<int, string>|string> $this */
    return PolicyExpectation::make($this, Policy::lines($max));
});

expect()->extend('toHaveMethodParametersAtMost', function (int $max): ArchExpectation {
    /** @var Expectation<array<int, string>|string> $this */
    return PolicyExpectation::make($this, Policy::parameters($max));
});
