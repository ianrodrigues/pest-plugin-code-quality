<?php

declare(strict_types=1);

arch('fixture app methods stay within limits')
    ->expect('Fixture\App')
    ->classes()
    ->toHaveMethodComplexityAtMost(10)
    ->toHaveMethodLinesAtMost(20)
    ->toHaveMethodParametersAtMost(4);
