<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

/**
 * Preparação para integração fiscal futura (NFC-e / NF-e).
 * Não utilizado ativamente — mantido para extensibilidade.
 */
enum FiscalStatusEnum: string
{
    case Pending   = 'pending';
    case Issued    = 'issued';
    case Cancelled = 'cancelled';
    case Error     = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendente',
            self::Issued    => 'Emitido',
            self::Cancelled => 'Cancelado',
            self::Error     => 'Erro',
        };
    }
}
