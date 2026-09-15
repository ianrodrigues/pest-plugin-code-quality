<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality;

use IanRodrigues\CodeQuality\Expectations\PolicyExpectation;
use IanRodrigues\CodeQuality\Policies\Policy;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Expectation;
use Pest\Plugin;

const VERSION = '0.1.0';

// Composer gives no ordering guarantee between packages' `files` entries, so
// `expect()` may not exist yet; Pest runs `Plugin::$callables` after boot.
// Registering right away when it does exist keeps static analysis aware of the
// methods, since PHPStan never boots Pest.
$register = static function (): void {
    expect()->extend('toHaveMethodComplexityAtMost', function (int $max, bool $allowEmpty = false): ArchExpectation {
        /** @var Expectation<array<int, string>|string> $this */
        return PolicyExpectation::make($this, Policy::complexity($max, $allowEmpty));
    });

    expect()->extend('toHaveMethodLinesAtMost', function (int $max, bool $allowEmpty = false): ArchExpectation {
        /** @var Expectation<array<int, string>|string> $this */
        return PolicyExpectation::make($this, Policy::lines($max, $allowEmpty));
    });

    expect()->extend('toHaveMethodParametersAtMost', function (int $max, bool $allowEmpty = false): ArchExpectation {
        /** @var Expectation<array<int, string>|string> $this */
        return PolicyExpectation::make($this, Policy::parameters($max, $allowEmpty));
    });

    expect()->extend('toHaveMethodsAtMost', function (int $max, bool $ignoringAccessors = false, bool $allowEmpty = false): ArchExpectation {
        /** @var Expectation<array<int, string>|string> $this */
        return PolicyExpectation::make($this, Policy::methods($max, $ignoringAccessors, $allowEmpty));
    });

    expect()->extend('toHavePropertiesAtMost', function (int $max, bool $allowEmpty = false): ArchExpectation {
        /** @var Expectation<array<int, string>|string> $this */
        return PolicyExpectation::make($this, Policy::properties($max, $allowEmpty));
    });
};

if (function_exists('expect')) {
    $register();
} else {
    Plugin::$callables[] = $register;
}
