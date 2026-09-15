<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Config;
use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Tests\TestCase;
use Pest\Arch\SingleArchExpectation;
use PHPUnit\Framework\ExpectationFailedException;

pest()->extend(TestCase::class)->in('Feature');

Config::baseline(__DIR__.'/quality-baseline.json');

const FIXTURE_APP = 'IanRodrigues\CodeQuality\Tests\Fixtures\App';
const FIXTURE_METRICS = 'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics';

/**
 * Architecture expectations verify lazily, on the first proxied call or on
 * destruction. Asking for verification is the only trigger that does not
 * also run a second, unrelated expectation.
 *
 * @param Closure(): mixed $chain
 * @return Closure(): void
 */
function policy(Closure $chain): Closure
{
    return function () use ($chain): void {
        $expectation = $chain();

        if ($expectation instanceof SingleArchExpectation) {
            $expectation->ensureLazyExpectationIsVerified();
        }
    };
}

/**
 * @param Closure(): mixed $chain
 */
function policy_failure(Closure $chain): string
{
    try {
        policy($chain)();
    } catch (QualityExpectationFailed $failure) {
        return $failure->getMessage();
    }

    throw new ExpectationFailedException('Expected the expectation to fail, but it passed.');
}
