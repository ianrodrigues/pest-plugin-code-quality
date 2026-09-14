<?php

declare(strict_types=1);

arch('the package keeps its own methods simple')
    ->expect('IanRodrigues\CodeQuality')
    ->classes()
    ->toHaveMethodComplexityAtMost(10);

arch('the package keeps its own methods short')
    ->expect('IanRodrigues\CodeQuality')
    ->classes()
    ->toHaveMethodLinesAtMost(40);

arch('the package keeps its own parameter lists small')
    ->expect('IanRodrigues\CodeQuality')
    ->classes()
    ->toHaveMethodParametersAtMost(4);
