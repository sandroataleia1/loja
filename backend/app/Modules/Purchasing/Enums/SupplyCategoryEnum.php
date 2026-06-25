<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Enums;

enum SupplyCategoryEnum: string
{
    case Materials  = 'materials';
    case Services   = 'services';
    case Transport  = 'transport';
    case Other      = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Materials => 'Materiais',
            self::Services  => 'Serviços',
            self::Transport => 'Transporte',
            self::Other     => 'Outros',
        };
    }
}
