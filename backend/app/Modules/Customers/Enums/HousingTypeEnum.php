<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum HousingTypeEnum: string
{
    case Own      = 'own';
    case Rented   = 'rented';
    case Financed = 'financed';
    case Family   = 'family';

    public function label(): string
    {
        return match ($this) {
            self::Own      => 'Própria',
            self::Rented   => 'Alugada',
            self::Financed => 'Financiada',
            self::Family   => 'Familiar',
        };
    }
}
