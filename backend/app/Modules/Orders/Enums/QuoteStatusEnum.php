<?php

declare(strict_types=1);

namespace App\Modules\Orders\Enums;

enum QuoteStatusEnum: string
{
    case Draft     = 'draft';
    case Sent      = 'sent';
    case Viewed    = 'viewed';
    case Accepted  = 'accepted';
    case Rejected  = 'rejected';
    case Expired   = 'expired';
    case Converted = 'converted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Rascunho',
            self::Sent      => 'Enviado',
            self::Viewed    => 'Visualizado',
            self::Accepted  => 'Aceito',
            self::Rejected  => 'Recusado',
            self::Expired   => 'Expirado',
            self::Converted => 'Convertido em Pedido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Sent      => 'blue',
            self::Viewed    => 'purple',
            self::Accepted  => 'green',
            self::Rejected  => 'red',
            self::Expired   => 'orange',
            self::Converted => 'teal',
            self::Cancelled => 'red',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Viewed], strict: true);
    }

    public function canConvert(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Viewed, self::Accepted], strict: true);
    }
}
