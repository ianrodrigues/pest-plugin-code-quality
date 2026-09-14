<?php

declare(strict_types=1);

/*
 * Editor support only, never autoloaded or executed. The expectations are
 * registered at runtime through `expect()->extend()`, so no source declares
 * them for an editor to complete.
 *
 * PHPStan gets the same three methods from `extension.neon` instead:
 * a stub redeclaring these types would drop the `@mixin` tags the rest of
 * the architecture chain relies on.
 */

namespace Pest {

    /**
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodComplexityAtMost(int $max)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodLinesAtMost(int $max)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodParametersAtMost(int $max)
     */
    class Expectation
    {
    }
}

namespace Pest\Arch {

    /**
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodComplexityAtMost(int $max)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodLinesAtMost(int $max)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodParametersAtMost(int $max)
     */
    class PendingArchExpectation
    {
    }
}

namespace Pest\Arch\Contracts {

    /**
     * @method ArchExpectation toHaveMethodComplexityAtMost(int $max)
     * @method ArchExpectation toHaveMethodLinesAtMost(int $max)
     * @method ArchExpectation toHaveMethodParametersAtMost(int $max)
     */
    interface ArchExpectation
    {
    }
}
