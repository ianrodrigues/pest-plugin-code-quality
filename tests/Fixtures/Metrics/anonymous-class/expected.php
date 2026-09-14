<?php

declare(strict_types=1);

/*
 * The anonymous class's own method (`greet`) is measured (ccn2: 2, lines: 3,
 * params: 1) but is never a target: it has no stable, addressable symbol
 * name, so it is intentionally left out of this table. Only the enclosing
 * real method is asserted here.
 */
return [
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\AnonymousClass\Fixture::makeGreeter' => [1, 6, 0],
];
