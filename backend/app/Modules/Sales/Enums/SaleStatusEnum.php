<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum SaleStatusEnum: string
{
    case Draft             = 'draft';
    case Pending           = 'pending';
    case Completed         = 'completed';
    case Cancelled         = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded          = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft             => 'Rascunho',
            self::Pending           => 'Pendente',
            self::Completed         => 'Concluída',
            self::Cancelled         => 'Cancelada',
            self::PartiallyRefunded => 'Parcialmente devolvida',
            self::Refunded          => 'Devolvida',
        };
    }

    /** Statuses que permitem adicionar pagamentos e descontos. */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Pending;
    }

    /** Statuses que permitem cancelamento. */
    public function isCancellable(): bool
    {
        return match ($this) {
            self::Draft, self::Pending, self::Completed => true,
            default                                     => false,
        };
    }

    /** Indica que o estoque já foi movimentado. */
    public function stockWasDecremented(): bool
    {
        return match ($this) {
            self::Completed, self::PartiallyRefunded => true,
            default                                  => false,
        };
    }
}
