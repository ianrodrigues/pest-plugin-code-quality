<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Expectations;

use IanRodrigues\CodeQuality\Analysis\AnalysisError;
use IanRodrigues\CodeQuality\Baseline\PolicyIdentity;
use IanRodrigues\CodeQuality\Config;
use IanRodrigues\CodeQuality\Exceptions\QualityAnalysisError;
use IanRodrigues\CodeQuality\Exceptions\QualityExpectationFailed;
use IanRodrigues\CodeQuality\Exceptions\UnsupportedModifier;
use IanRodrigues\CodeQuality\Policies\Policy;
use IanRodrigues\CodeQuality\Policies\PolicyResult;
use IanRodrigues\CodeQuality\Policies\PolicyRunner;
use IanRodrigues\CodeQuality\Policies\Targets;
use IanRodrigues\CodeQuality\Reporting\PolicyLocation;
use IanRodrigues\CodeQuality\Reporting\RunRecorder;
use IanRodrigues\CodeQuality\Reporting\TestIdentity;
use IanRodrigues\CodeQuality\Support\Negation;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\Options\LayerOptions;
use Pest\Arch\SingleArchExpectation;
use Pest\Expectation;
use Throwable;

final class PolicyExpectation
{
    /**
     * @param Expectation<array<int, string>|string> $expectation
     */
    public static function make(Expectation $expectation, Policy $policy): ArchExpectation
    {
        if (Negation::isActive()) {
            throw UnsupportedModifier::not();
        }

        $targets = Targets::fromExpectation($expectation);

        /*
         * Captured now, synchronously with the `->toHaveMethodXAtMost()`
         * call: by the time the expectation below actually verifies, it
         * may be running from a destructor with no useful call stack left.
         */
        $location = PolicyLocation::capture();

        return SingleArchExpectation::fromExpectation(
            $expectation,
            static function (LayerOptions $options) use ($targets, $policy, $location): void {
                $result = self::verify($targets, $policy, $location, $options);

                RunRecorder::record($result, $location, $targets->values(), array_values($options->exclude));

                if ($result->passed()) {
                    return;
                }

                throw QualityExpectationFailed::fromResult($result);
            },
        );
    }

    /**
     * Anything that stops a policy short of a result is recorded before it
     * is rethrown, so the end of the run can tell a complete scan from one
     * with a hole in it.
     */
    private static function verify(
        Targets $targets,
        Policy $policy,
        PolicyLocation $location,
        LayerOptions $options,
    ): PolicyResult {
        $identity = PolicyIdentity::for($targets->values(), $policy, TestIdentity::current());

        try {
            if (Config::baselinePath() !== null) {
                RunRecorder::ensureUnique($identity, $location);
            }

            return PolicyRunner::shared()->run($policy, $targets, $options, $identity);
        } catch (AnalysisError $error) {
            $failure = QualityAnalysisError::fromAnalysisError($error);

            RunRecorder::recordError($identity, $policy, $location, $targets->values(), $failure->getMessage());

            throw $failure;
        } catch (Throwable $error) {
            RunRecorder::recordError($identity, $policy, $location, $targets->values(), $error->getMessage());

            throw $error;
        }
    }
}
