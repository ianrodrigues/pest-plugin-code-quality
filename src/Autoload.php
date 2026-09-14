<?php

declare(strict_types=1);

namespace Rdgs\PestCodeQuality;

/**
 * The package version. Bumped alongside CHANGELOG.md releases.
 */
const VERSION = '0.1.0';

/**
 * Marker function proving this file was loaded via `autoload.files`.
 *
 * No product behaviour lives here yet; later tasks register expectations
 * from this same entry point.
 */
function version(): string
{
    return VERSION;
}
