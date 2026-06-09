<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalDocumentTypeEnum: string
{
    case Nfce = 'nfce'; // Nota Fiscal Consumidor Eletrônica
    case Nfe  = 'nfe';  // Nota Fiscal Eletrônica

    public function label(): string
    {
        return match ($this) {
            self::Nfce => 'NFC-e',
            self::Nfe  => 'NF-e',
        };
    }

    /** Modelo fiscal conforme SEFAZ (55=NF-e, 65=NFC-e). */
    public function modelo(): int
    {
        return match ($this) {
            self::Nfe  => 55,
            self::Nfce => 65,
        };
    }
}
