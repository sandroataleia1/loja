<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Enums;

enum PurchaseOrderStatusEnum: string
{
    case Draft             = 'draft';
    case Sent              = 'sent';
    case PartiallyReceived = 'partially_received';
    case Received          = 'received';
    case Cancelled         = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft             => 'Rascunho',
            self::Sent              => 'Enviado',
            self::PartiallyReceived => 'Parcialmente Recebido',
            self::Received          => 'Recebido',
            self::Cancelled         => 'Cancelado',
        };
    }

    public function canReceive(): bool
    {
        return in_array($this, [self::Sent, self::PartiallyReceived]);
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Draft, self::Sent]);
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Received, self::Cancelled]);
    }
}
