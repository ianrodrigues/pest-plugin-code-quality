<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality\Tests\Fixtures\App\Parsing;

final class Parser
{
    public function parse(string $input): array
    {
        $tokens = [];

        foreach (explode(' ', $input) as $chunk) {
            if ($chunk === '') {
                continue;
            }

            if (str_starts_with($chunk, '#') || str_starts_with($chunk, '//')) {
                break;
            }

            $tokens[] = is_numeric($chunk) ? (int) $chunk : $chunk;
        }

        return $tokens;
    }
}
