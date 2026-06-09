<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalEventTypeEnum: string
{
    case Issue       = 'issue';
    case Authorize   = 'authorize';
    case Reject      = 'reject';
    case Cancel      = 'cancel';
    case Contingency = 'contingency';
    case Resend      = 'resend';

    public function label(): string
    {
        return match ($this) {
            self::Issue       => 'Emissão',
            self::Authorize   => 'Autorização',
            self::Reject      => 'Rejeição',
            self::Cancel      => 'Cancelamento',
            self::Contingency => 'Contingência',
            self::Resend      => 'Reenvio',
        };
    }
}
