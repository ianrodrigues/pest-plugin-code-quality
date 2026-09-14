<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\Invalid;

final class Fixture
{
    public function broken(int $x): int
    {
        if ($x > 0) {
            return 1;
        // deliberately missing the closing brace for the if-block and the
        // method, and the trailing semicolon, so this file cannot be parsed
