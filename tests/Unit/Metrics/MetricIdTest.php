<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Metrics\MetricId;

it('builds ccn2, lines and params identities at version 1 by default', function (): void {
    expect((string) MetricId::ccn2())->toBe('ccn2@1');
    expect((string) MetricId::lines())->toBe('lines@1');
    expect((string) MetricId::params())->toBe('params@1');
});

it('accepts an explicit version', function (): void {
    expect((string) MetricId::ccn2(2))->toBe('ccn2@2');
});

it('considers two identities equal only when the name and version match', function (): void {
    expect(MetricId::ccn2()->equals(MetricId::ccn2()))->toBeTrue();
    expect(MetricId::ccn2()->equals(MetricId::ccn2(2)))->toBeFalse();
    expect(MetricId::ccn2()->equals(MetricId::lines()))->toBeFalse();
});

it('exposes the name and version as readonly properties', function (): void {
    $id = MetricId::params();

    expect($id->name)->toBe('params');
    expect($id->version)->toBe(1);
});
