<?php

declare(strict_types=1);

/*
 * Editor support only, never autoloaded or executed; expectations are
 * registered at runtime via `expect()->extend()`. PHPStan reads these
 * same methods from `extension.neon`, which also carries the `@mixin` tags.
 */

namespace Pest {

    /**
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodComplexityAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodLinesAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodParametersAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodsAtMost(int $max, bool $ignoringAccessors = false, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHavePropertiesAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveInheritanceDepthAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveClassLinesAtMost(int $max, bool $allowEmpty = false)
     */
    class Expectation
    {
    }
}

namespace Pest\Arch {

    /**
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodComplexityAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodLinesAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodParametersAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveMethodsAtMost(int $max, bool $ignoringAccessors = false, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHavePropertiesAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveInheritanceDepthAtMost(int $max, bool $allowEmpty = false)
     * @method \Pest\Arch\Contracts\ArchExpectation toHaveClassLinesAtMost(int $max, bool $allowEmpty = false)
     */
    class PendingArchExpectation
    {
    }
}

namespace Pest\Arch\Contracts {

    /**
     * @method ArchExpectation toHaveMethodComplexityAtMost(int $max, bool $allowEmpty = false)
     * @method ArchExpectation toHaveMethodLinesAtMost(int $max, bool $allowEmpty = false)
     * @method ArchExpectation toHaveMethodParametersAtMost(int $max, bool $allowEmpty = false)
     * @method ArchExpectation toHaveMethodsAtMost(int $max, bool $ignoringAccessors = false, bool $allowEmpty = false)
     * @method ArchExpectation toHavePropertiesAtMost(int $max, bool $allowEmpty = false)
     * @method ArchExpectation toHaveInheritanceDepthAtMost(int $max, bool $allowEmpty = false)
     * @method ArchExpectation toHaveClassLinesAtMost(int $max, bool $allowEmpty = false)
     */
    interface ArchExpectation
    {
    }
}
