<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum FinancialEntryStatusEnum: string
{
    case Pending       = 'pending';
    case Paid          = 'paid';
    case Overdue       = 'overdue';
    case Cancelled     = 'cancelled';
    case PartiallyPaid = 'partially_paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending       => 'Pendente',
            self::Paid          => 'Pago',
            self::Overdue       => 'Vencido',
            self::Cancelled     => 'Cancelado',
            self::PartiallyPaid => 'Parcialmente pago',
        };
    }

    public function canPay(): bool
    {
        return match ($this) {
            self::Pending, self::Overdue, self::PartiallyPaid => true,
            default                                           => false,
        };
    }

    public function canCancel(): bool
    {
        return $this !== self::Cancelled;
    }

    /** Indica que o lançamento impactou o saldo da conta. */
    public function affectedBalance(): bool
    {
        return $this === self::Paid;
    }
}
