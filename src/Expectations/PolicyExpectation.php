<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Expectations;

use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\Options\LayerOptions;
use Pest\Arch\SingleArchExpectation;
use Pest\Expectation;
use Rdgs\PestCodeQuality\Analysis\AnalysisError;
use Rdgs\PestCodeQuality\Exceptions\QualityAnalysisError;
use Rdgs\PestCodeQuality\Exceptions\QualityExpectationFailed;
use Rdgs\PestCodeQuality\Exceptions\UnsupportedModifier;
use Rdgs\PestCodeQuality\Policies\Policy;
use Rdgs\PestCodeQuality\Policies\PolicyRunner;
use Rdgs\PestCodeQuality\Policies\Targets;
use Rdgs\PestCodeQuality\Support\Negation;

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

        return SingleArchExpectation::fromExpectation(
            $expectation,
            static function (LayerOptions $options) use ($targets, $policy): void {
                try {
                    $result = PolicyRunner::shared()->run($policy, $targets, $options);
                } catch (AnalysisError $error) {
                    throw QualityAnalysisError::fromAnalysisError($error);
                }

                if ($result->passed()) {
                    return;
                }

                throw QualityExpectationFailed::fromResult($result);
            },
        );
    }
}
