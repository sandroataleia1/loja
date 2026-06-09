<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum PaymentStatusEnum: string
{
    case Pending  = 'pending';
    case Paid     = 'paid';
    case Failed   = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pendente',
            self::Paid     => 'Pago',
            self::Failed   => 'Falhou',
            self::Refunded => 'Estornado',
        };
    }
}
