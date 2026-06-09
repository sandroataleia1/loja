<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Catalog\Http\Resources\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InventoryBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'store_id'           => $this->store_id,
            'variant_id'         => $this->variant_id,
            'quantity'           => $this->quantity,
            'reserved_quantity'  => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'store'              => $this->whenLoaded('store',   fn () => new StoreResource($this->store)),
            'variant'            => $this->whenLoaded('variant', fn () => new VariantResource($this->variant)),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
