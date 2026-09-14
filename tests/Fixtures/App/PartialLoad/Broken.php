<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\PartialLoad;

// `MissingParent` is never declared anywhere in the fixture app: the
// class fails to autoload, exercising the "not loadable" warning.
final class Broken extends MissingParent
{
    public function run(): void
    {
    }
}
