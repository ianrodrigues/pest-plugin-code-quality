<?php

declare(strict_types=1);

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/** @return list<string> */
function naming_conventions_violations(string $directory): array
{
    $parser = (new ParserFactory())->createForNewestSupportedVersion();
    $finder = new NodeFinder();
    $violations = [];

    foreach (Finder::create()->files()->in($directory)->name('*.php') as $file) {
        $path = $file->getPathname();
        $ast = $parser->parse($file->getContents()) ?? [];

        foreach ($finder->findInstanceOf($ast, ClassMethod::class) as $method) {
            $name = $method->name->toString();

            if (preg_match('/^(__[a-zA-Z]+|[a-z][a-zA-Z0-9]*)$/', $name) !== 1) {
                $violations[] = sprintf('%s:%d method %s() must be camelCase', $path, $method->getStartLine(), $name);
            }
        }

        foreach ($finder->findInstanceOf($ast, Function_::class) as $function) {
            $name = $function->name->toString();

            if (preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1) {
                $violations[] = sprintf('%s:%d function %s() must be snake_case', $path, $function->getStartLine(), $name);
            }
        }
    }

    return $violations;
}

it('names class methods in camelCase and standalone functions in snake_case', function (): void {
    expect(naming_conventions_violations(dirname(__DIR__, 2).'/src'))->toBe([]);
});
