<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum ProductVisibilityEnum: string
{
    case Private  = 'PRIVATE';
    case Public   = 'PUBLIC';
    case Unlisted = 'UNLISTED';

    public function label(): string
    {
        return match ($this) {
            self::Private  => 'Privado',
            self::Public   => 'Público',
            self::Unlisted => 'Link Direto',
        };
    }
}
