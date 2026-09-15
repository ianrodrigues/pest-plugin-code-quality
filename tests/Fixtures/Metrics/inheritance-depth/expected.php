<?php

declare(strict_types=1);

return [
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Shape::area' => [null, null, 0],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Root::area' => [1, 1, 0],

    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Shape' => [
        'methods' => 1,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => null,
        'classLines' => 1,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Root' => [
        'methods' => 1,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => 0,
        'classLines' => 2,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Middle' => [
        'methods' => 0,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => 1,
        'classLines' => 0,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Leaf' => [
        'methods' => 0,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => 2,
        'classLines' => 0,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Fixture' => [
        'methods' => 0,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => 3,
        'classLines' => 0,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Reported' => [
        'methods' => 0,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => 2,
        'classLines' => 0,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\InheritanceDepth\Visiting' => [
        'methods' => 0,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => 1,
        'classLines' => 0,
    ],
];
