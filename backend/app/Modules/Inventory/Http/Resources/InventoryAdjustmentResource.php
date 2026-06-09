<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Inventory\Models\InventoryAdjustment
 */
final class InventoryAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'tenant_id'         => $this->tenant_id,
            'store_id'          => $this->store_id,
            'variant_id'        => $this->variant_id,
            'movement_id'       => $this->movement_id,
            'previous_quantity' => $this->previous_quantity,
            'new_quantity'      => $this->new_quantity,
            'difference'        => $this->difference,
            'reason'            => $this->reason,
            'created_by'        => $this->created_by,
            'created_at'        => $this->created_at?->toISOString(),
            'store'   => $this->whenLoaded('store',   fn () => ['uuid' => $this->store->uuid,   'name' => $this->store->name,   'code' => $this->store->code]),
            'variant' => $this->whenLoaded('variant', fn () => ['uuid' => $this->variant->uuid, 'sku'  => $this->variant->sku,  'name' => $this->variant->name]),
        ];
    }
}
