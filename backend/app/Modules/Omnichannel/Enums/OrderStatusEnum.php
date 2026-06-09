<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Enums;

enum OrderStatusEnum: string
{
    case Pending   = 'pending';    // order placed, awaiting payment confirmation
    case Paid      = 'paid';       // payment confirmed
    case Fulfilled = 'fulfilled';  // shipped / picked up / Sale created
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Aguardando pagamento',
            self::Paid      => 'Pago',
            self::Fulfilled => 'Entregue',
            self::Cancelled => 'Cancelado',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending   => in_array($next, [self::Paid, self::Cancelled], true),
            self::Paid      => in_array($next, [self::Fulfilled, self::Cancelled], true),
            self::Fulfilled => false,
            self::Cancelled => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Fulfilled, self::Cancelled], true);
    }
}
