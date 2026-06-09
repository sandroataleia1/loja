<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum TransferStatusEnum: string
{
    case Pending   = 'pending';
    case InTransit = 'in_transit';
    case Received  = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendente',
            self::InTransit => 'Em trânsito',
            self::Received  => 'Recebida',
            self::Cancelled => 'Cancelada',
        };
    }

    public function canDispatch(): bool  { return $this === self::Pending; }
    public function canReceive(): bool   { return $this === self::InTransit; }
    public function canCancel(): bool    { return $this === self::Pending || $this === self::InTransit; }
}
