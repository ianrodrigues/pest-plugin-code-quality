<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Pest's architecture plugin mixes `arch()` into every test case at
 * runtime, so nothing declares it where static analysis can see it.
 *
 * @method \Pest\Arch\Options\TestCaseOptions arch()
 */
abstract class TestCase extends BaseTestCase
{
    //
}
