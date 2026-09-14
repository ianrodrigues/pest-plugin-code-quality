<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Analysis\AnalysisError;
use IanRodrigues\CodeQuality\Analysis\AstMeasurer;
use IanRodrigues\CodeQuality\Exceptions\QualityAnalysisError;
use PHPUnit\Framework\AssertionFailedError;

it('is a runtime error, never an assertion failure, so the owning test ends as an error', function (): void {
    $error = QualityAnalysisError::fromAnalysisError(AnalysisError::unreadable('app/Missing.php'));

    expect($error)->not->toBeInstanceOf(AssertionFailedError::class);
});

it('prefixes the analysis error message with a distinct heading', function (): void {
    $error = QualityAnalysisError::fromAnalysisError(
        AnalysisError::unparsable('app/Broken.php', 'syntax error, unexpected end of file'),
    );

    expect($error->getMessage())->toBe(
        'Quality analysis error: Could not parse "app/Broken.php": syntax error, unexpected end of file',
    );
});

it('carries the path for an unreadable file', function (): void {
    $error = QualityAnalysisError::fromAnalysisError(AnalysisError::unreadable('app/Missing.php'));

    expect($error->getMessage())->toBe('Quality analysis error: Could not read "app/Missing.php".');
});

it('preserves the original analysis error as the previous exception', function (): void {
    $original = AnalysisError::unreadable('app/Missing.php');
    $error = QualityAnalysisError::fromAnalysisError($original);

    expect($error->getPrevious())->toBe($original);
});

it('wraps the genuine parser error a broken fixture raises', function (): void {
    $path = dirname(__DIR__, 2).'/Fixtures/App/Broken/SyntaxError.php';

    try {
        new AstMeasurer()->measure($path);

        $this->fail('Expected the broken fixture to raise an analysis error.');
    } catch (AnalysisError $analysisError) {
        $error = QualityAnalysisError::fromAnalysisError($analysisError);
    }

    expect($error->getMessage())->toStartWith('Quality analysis error: Could not parse')
        ->and($error->getMessage())->toContain($path)
        ->and($error)->not->toBeInstanceOf(AssertionFailedError::class);
});
