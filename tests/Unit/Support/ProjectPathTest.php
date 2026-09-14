<?php

declare(strict_types=1);

use IanRodrigues\CodeQuality\Support\ProjectPath;
use Pest\TestSuite;

it('normalises windows-style separators to forward slashes under the root', function (): void {
    $root = TestSuite::getInstance()->rootPath;
    $windowsPath = $root.'\\tests\\Fixtures\\App\\Billing\\Invoice.php';

    expect(ProjectPath::relative($windowsPath))->toBe('tests/Fixtures/App/Billing/Invoice.php');
});

it('normalises windows-style separators for a path outside the root', function (): void {
    expect(ProjectPath::relative('C:\\Users\\dev\\File.php'))->toBe('C:/Users/dev/File.php');
});

it('leaves a forward-slash path outside the root unchanged', function (): void {
    expect(ProjectPath::relative('/somewhere/else/File.php'))->toBe('/somewhere/else/File.php');
});

it('strips only the root, never a path that merely shares its prefix', function (): void {
    $root = TestSuite::getInstance()->rootPath;
    $sibling = $root.'Sibling/File.php';

    expect(ProjectPath::relative($sibling))->toBe(str_replace('\\', '/', $sibling));
});
