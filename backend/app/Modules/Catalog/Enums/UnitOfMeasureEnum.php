<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum UnitOfMeasureEnum: string
{
    case UN = 'UN'; // Unidade
    case M  = 'M';  // Metro linear
    case M2 = 'M2'; // Metro quadrado
    case M3 = 'M3'; // Metro cúbico
    case KG = 'KG'; // Quilograma
    case LT = 'LT'; // Litro
    case CX = 'CX'; // Caixa
    case SC = 'SC'; // Saco

    public function label(): string
    {
        return match($this) {
            self::UN => 'Unidade',
            self::M  => 'Metro linear',
            self::M2 => 'Metro quadrado',
            self::M3 => 'Metro cúbico',
            self::KG => 'Quilograma',
            self::LT => 'Litro',
            self::CX => 'Caixa',
            self::SC => 'Saco',
        };
    }

    public function isDecimalAllowed(): bool
    {
        return in_array($this, [self::M, self::M2, self::M3, self::KG, self::LT], true);
    }
}
