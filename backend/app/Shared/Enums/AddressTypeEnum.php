<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum AddressTypeEnum: string
{
    case Delivery     = 'delivery';
    case Billing      = 'billing';
    case Commercial   = 'commercial';
    case Headquarters = 'headquarters';

    public function label(): string
    {
        return match ($this) {
            self::Delivery     => 'Entrega',
            self::Billing      => 'Cobrança',
            self::Commercial   => 'Comercial',
            self::Headquarters => 'Sede',
        };
    }
}
