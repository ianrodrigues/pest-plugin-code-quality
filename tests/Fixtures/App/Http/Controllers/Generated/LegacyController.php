<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Http\Controllers\Generated;

final class LegacyController
{
    public function handle(
        array $payload,
        ?string $locale,
        bool $strict,
        ?int $version,
        ?string $channel
    ): array {
        $locale = $locale ?? 'en';
        $channel = $channel ?? 'web';
        $version = $version ?? 1;
        $flags = [];
        $errors = [];
        $seen = 0;

        if ($strict) {
            $flags[] = 'strict';
        }

        if ($version > 1 || $channel === 'api') {
            $flags[] = 'modern';
        }

        foreach ($payload as $key => $value) {
            $seen++;

            if ($value === null) {
                $errors[] = $key;

                continue;
            }

            if (is_array($value) && $key !== 'meta') {
                $flags[] = (string) $key;
            }
        }

        $label = match ($locale) {
            'en' => 'english',
            'pt' => 'portuguese',
            default => 'other',
        };

        for ($index = 0; $index < $version; $index++) {
            $flags[] = 'v'.$index;
        }

        do {
            $flags[] = 'sealed';
        } while (false);

        sort($flags);
        sort($errors);

        $summary = [
            'label' => $label,
            'channel' => $channel,
            'locale' => $locale,
            'version' => $version,
            'seen' => $seen,
            'flags' => $flags,
            'errors' => $errors,
        ];

        $summary['count'] = count($flags);
        $summary['failed'] = $errors !== [];

        return $summary;
    }
}
