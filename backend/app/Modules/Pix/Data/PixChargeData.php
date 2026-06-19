<?php

declare(strict_types=1);

namespace App\Modules\Pix\Data;

final class PixChargeData
{
    public function __construct(
        public readonly string  $externalId,
        public readonly string  $qrCodeImage,    // base64-encoded PNG
        public readonly string  $pixCopyPaste,   // copia-e-cola string
        public readonly string  $expiresAt,      // ISO 8601 datetime
        public readonly string  $status,         // pending | paid | expired
        public readonly array   $rawResponse = [],
    ) {}
}
