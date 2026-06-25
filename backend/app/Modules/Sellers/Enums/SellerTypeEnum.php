<?php

declare(strict_types=1);

namespace App\Modules\Sellers\Enums;

enum SellerTypeEnum: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Interno',
            self::External => 'Externo (Representante)',
        };
    }
}
