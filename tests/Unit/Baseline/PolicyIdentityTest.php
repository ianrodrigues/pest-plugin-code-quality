<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Baseline\PolicyIdentity;
use IanRodrigues\CodeQuality\Policies\Policy;

it('uses the test description and the metric name', function (): void {
    $identity = PolicyIdentity::for(['App\Http\Controllers'], Policy::complexity(10), 'controllers remain easy to change');

    expect($identity)->toBe('controllers remain easy to change :: ccn2');
});

it('gives each metric on one chain its own identity', function (): void {
    $description = 'controllers remain easy to change';

    expect(PolicyIdentity::for(['App'], Policy::lines(40), $description))->toBe("{$description} :: lines")
        ->and(PolicyIdentity::for(['App'], Policy::parameters(4), $description))->toBe("{$description} :: params");
});

it('ignores the limit, so changing it keeps the same identity', function (): void {
    expect(PolicyIdentity::for(['App'], Policy::complexity(10), 'small methods'))
        ->toBe(PolicyIdentity::for(['App'], Policy::complexity(12), 'small methods'));
});

it('falls back to the sorted targets and the expectation name without a description', function (): void {
    $identity = PolicyIdentity::for(['App\Models', 'App\Http'], Policy::complexity(10), '');

    expect($identity)->toBe('[App\Http, App\Models] :: toHaveMethodComplexityAtMost');
});

it('reads the same whatever order the targets were written in', function (): void {
    expect(PolicyIdentity::for(['App\Models', 'App\Http'], Policy::lines(40), ''))
        ->toBe(PolicyIdentity::for(['App\Http', 'App\Models'], Policy::lines(40), ''));
});

it('falls back when Pest named the test after the chain itself', function (): void {
    $generated = "expect 'App\Models' → classes → toHaveMethodComplexityAtMost 10";

    expect(PolicyIdentity::for(['App\Models'], Policy::complexity(10), $generated))
        ->toBe('[App\Models] :: toHaveMethodComplexityAtMost');
});

it('keeps a written description that merely mentions expecting', function (): void {
    expect(PolicyIdentity::for(['App'], Policy::complexity(10), 'expect controllers to stay small'))
        ->toBe('expect controllers to stay small :: ccn2');
});

it('falls back when no test is running', function (): void {
    expect(PolicyIdentity::for(['App'], Policy::complexity(10), 'unknown'))
        ->toBe('[App] :: toHaveMethodComplexityAtMost');
});

it('gives a written description a different identity with ignoringAccessors than without', function (): void {
    $withFlag = PolicyIdentity::for(['App'], Policy::methods(5, ignoringAccessors: true), 'small classes');
    $withoutFlag = PolicyIdentity::for(['App'], Policy::methods(5), 'small classes');

    expect($withFlag)->not->toBe($withoutFlag)
        ->and($withoutFlag)->toBe('small classes :: methods');
});

it('gives a target-based identity a different identity with ignoringAccessors than without', function (): void {
    $withFlag = PolicyIdentity::for(['App\Models'], Policy::methods(5, ignoringAccessors: true), '');
    $withoutFlag = PolicyIdentity::for(['App\Models'], Policy::methods(5), '');

    expect($withFlag)->not->toBe($withoutFlag)
        ->and($withoutFlag)->toBe('[App\Models] :: toHaveMethodsAtMost');
});
