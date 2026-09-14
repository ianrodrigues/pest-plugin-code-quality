<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withSkip([
        __DIR__.'/tests/Fixtures',
        __DIR__.'/tests/Performance/generate-project.php',
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        instanceOf: true,
    )
    ->withSets([PestSetList::CODING_STYLE])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
