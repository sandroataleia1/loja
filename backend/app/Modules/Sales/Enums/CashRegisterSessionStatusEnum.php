<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum CashRegisterSessionStatusEnum: string
{
    case Open      = 'open';
    case Closed    = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open      => 'Aberto',
            self::Closed    => 'Fechado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function canClose(): bool { return $this === self::Open; }
    public function isOpen(): bool   { return $this === self::Open; }
    public function isClosed(): bool { return $this === self::Closed; }
}
