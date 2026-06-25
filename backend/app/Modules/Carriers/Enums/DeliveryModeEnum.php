<?php

declare(strict_types=1);

namespace App\Modules\Carriers\Enums;

enum DeliveryModeEnum: string
{
    case OwnFleet   = 'own_fleet';
    case Outsourced = 'outsourced';
    case Motorcycle = 'motorcycle';
    case PostOffice = 'post_office';
    case App        = 'app';

    public function label(): string
    {
        return match ($this) {
            self::OwnFleet   => 'Frota Própria',
            self::Outsourced => 'Terceirizado',
            self::Motorcycle => 'Motoboy',
            self::PostOffice => 'Correios',
            self::App        => 'Aplicativo',
        };
    }
}
