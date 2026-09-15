<?php

declare(strict_types=1);

return [
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Timestamps::touch' => [1, 2, 0],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Base::describe' => [1, 1, 0],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Fixture::__construct' => [1, 0, 2],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Fixture::name' => [1, 1, 0],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Fixture::setName' => [1, 2, 1],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Fixture::setSize' => [1, 1, 1],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Fixture::summary' => [1, 1, 0],

    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Timestamps' => [
        'methods' => 1,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => null,
        'classLines' => 3,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Base' => [
        'methods' => 1,
        'accessors' => 0,
        'properties' => 0,
        'inheritance' => 0,
        'classLines' => 2,
    ],
    'IanRodrigues\CodeQuality\Tests\Fixtures\Metrics\ClassMethods\Fixture' => [
        'methods' => 5,
        'accessors' => 3,
        'properties' => 4,
        'inheritance' => 1,
        'classLines' => 13,
    ],
];
