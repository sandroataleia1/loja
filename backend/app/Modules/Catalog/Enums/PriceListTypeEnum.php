<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum PriceListTypeEnum: string
{
    case Retail         = 'retail';
    case Wholesale      = 'wholesale';
    case Representative = 'representative';
    case Special        = 'special';
    case Cost           = 'cost';

    public function label(): string
    {
        return match($this) {
            self::Retail         => 'Varejo',
            self::Wholesale      => 'Atacado',
            self::Representative => 'Representante',
            self::Special        => 'Especial',
            self::Cost           => 'Custo',
        };
    }
}
