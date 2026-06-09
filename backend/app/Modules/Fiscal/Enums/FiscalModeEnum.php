<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalModeEnum: string
{
    case None = 'none';
    case Nfce = 'nfce';
    case Nfe  = 'nfe';

    public function label(): string
    {
        return match($this) {
            self::None => 'Sem emissão fiscal',
            self::Nfce => 'NFC-e (consumidor final)',
            self::Nfe  => 'NF-e (pessoa jurídica)',
        };
    }

    public function requiresFiscalDocument(): bool
    {
        return $this !== self::None;
    }

    public function documentType(): ?FiscalDocumentTypeEnum
    {
        return match($this) {
            self::None => null,
            self::Nfce => FiscalDocumentTypeEnum::Nfce,
            self::Nfe  => FiscalDocumentTypeEnum::Nfe,
        };
    }
}
