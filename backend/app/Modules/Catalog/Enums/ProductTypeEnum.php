<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum ProductTypeEnum: string
{
    case Simple   = 'simple';
    case Variable = 'variable';
    case Kit      = 'kit';

    public function label(): string
    {
        return match($this) {
            self::Simple   => 'Simples',
            self::Variable => 'Com variantes',
            self::Kit      => 'Kit / Combo',
        };
    }

    public function hasVariants(): bool
    {
        return $this === self::Variable;
    }
}
