<?php

declare(strict_types=1);

use Rdgs\PestCodeQuality\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| No product behaviour exists yet, so this bootstrap only binds the shared
| PHPUnit test case for Feature tests. Nothing else is configured here
| until later tasks need it.
|
*/

pest()->extend(TestCase::class)->in('Feature');
