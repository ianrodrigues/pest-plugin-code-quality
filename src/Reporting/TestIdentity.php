<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Reporting;

use Pest\TestSuite;
use PHPUnit\Framework\TestCase;

/**
 * The human-readable name of whichever test is currently running, read off
 * `TestSuite::getInstance()->test` rather than PHPUnit's own method name,
 * since Pest compiles `it('...')`/`arch('...')` descriptions into
 * machine-safe method names that no longer read like the original string.
 */
final class TestIdentity
{
    private function __construct()
    {
    }

    public static function current(): string
    {
        $test = TestSuite::getInstance()->test;

        if (! $test instanceof TestCase) {
            return 'unknown';
        }

        $method = 'getPrintableTestCaseMethodName';

        if (method_exists($test, $method)) {
            $name = $test->$method();

            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return $test->name();
    }
}
