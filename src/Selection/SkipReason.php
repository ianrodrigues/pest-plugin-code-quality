<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Selection;

/**
 * Why a file that was found under a target's resolved directories never
 * became a measurable object.
 */
enum SkipReason: string
{
    case NotLoadable = 'not loadable';
    case NamespaceMismatch = 'namespace mismatch';
    case Vendor = 'vendor';
    case NoAst = 'no ast';

    public function label(): string
    {
        return $this->value;
    }
}
