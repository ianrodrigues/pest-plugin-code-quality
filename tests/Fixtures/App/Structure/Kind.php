<?php

declare(strict_types=1);

namespace IanRodrigues\CodeQuality\Tests\Fixtures\App\Structure;

enum Kind: string
{
    case Draft = 'draft';

    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
