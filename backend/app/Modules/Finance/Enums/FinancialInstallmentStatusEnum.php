<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum FinancialInstallmentStatusEnum: string
{
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Overdue   = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendente',
            self::Paid      => 'Pago',
            self::Overdue   => 'Vencido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function canPay(): bool
    {
        return match ($this) {
            self::Pending, self::Overdue => true,
            default                      => false,
        };
    }
}
