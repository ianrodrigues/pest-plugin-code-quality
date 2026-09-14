<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests;

use Pest\Arch\Options\TestCaseOptions;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Pest's architecture plugin mixes `arch()` into every test case at
 * runtime, so nothing declares it where static analysis can see it.
 *
 * @method TestCaseOptions arch()
 */
abstract class TestCase extends BaseTestCase
{
    //
}
