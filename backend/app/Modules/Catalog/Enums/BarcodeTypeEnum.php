<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum BarcodeTypeEnum: string
{
    case Ean13   = 'ean13';
    case Ean8    = 'ean8';
    case Dun14   = 'dun14';
    case Code128 = 'code128';
    case QrCode  = 'qrcode';
    case Custom  = 'custom';

    public function label(): string
    {
        return match($this) {
            self::Ean13   => 'EAN-13',
            self::Ean8    => 'EAN-8',
            self::Dun14   => 'DUN-14',
            self::Code128 => 'Code 128',
            self::QrCode  => 'QR Code',
            self::Custom  => 'Personalizado',
        };
    }
}
