<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Inventory\Models\StockReservation
 */
final class StockReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'tenant_id'      => $this->tenant_id,
            'store_id'       => $this->store_id,
            'variant_id'     => $this->variant_id,
            'quantity'       => $this->quantity,
            'status'         => $this->status,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'expires_at'     => $this->expires_at?->toISOString(),
            'released_at'    => $this->released_at?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
