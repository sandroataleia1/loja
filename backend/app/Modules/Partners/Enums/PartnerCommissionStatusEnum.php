<?php

declare(strict_types=1);

namespace App\Modules\Partners\Enums;

enum PartnerCommissionStatusEnum: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Paid      = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendente',
            self::Confirmed => 'Confirmada',
            self::Paid      => 'Paga',
        };
    }
}
