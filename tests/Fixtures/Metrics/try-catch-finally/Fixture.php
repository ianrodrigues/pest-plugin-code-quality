<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\Metrics\TryCatchFinally;

use RuntimeException;
use Throwable;

final class Fixture
{
    // ccn2: 1 + catch = 2 (try and finally do not count)
    public function guarded(): string
    {
        try {
            return 'ok';
        } catch (RuntimeException $exception) {
            return 'failed';
        } finally {
            $this->cleanup();
        }
    }

    // ccn2: 1 + catch + catch = 3 (multiple catch blocks each count once)
    public function multiCatch(): string
    {
        try {
            return 'ok';
        } catch (RuntimeException $exception) {
            return 'runtime';
        } catch (Throwable $exception) {
            return 'other';
        }
    }

    private function cleanup(): void
    {
        // no-op
    }
}
