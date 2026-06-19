<?php

declare(strict_types=1);

namespace App\Modules\Pix\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Pix\Models\PixCharge */
final class PixChargeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'sale_id'        => $this->sale_id,
            'gateway'        => $this->gateway,
            'external_id'    => $this->external_id,
            'status'         => $this->status,
            'amount_cents'   => $this->amount_cents,
            'qr_code_image'  => $this->qr_code_image,
            'pix_copy_paste' => $this->pix_copy_paste,
            'expires_at'     => $this->expires_at?->toIso8601String(),
            'paid_at'        => $this->paid_at?->toIso8601String(),
        ];
    }
}
