<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'product_variant_id' => $this->product_variant_id,
            'quantity'           => $this->quantity,
            'unit_cost'          => $this->unit_cost,
            'total_cost'         => $this->total_cost,
            'received_quantity'  => $this->received_quantity,
            'pending_quantity'   => $this->pendingQuantity(),
            'variant'            => $this->whenLoaded('variant', fn () => [
                'uuid' => $this->variant->uuid,
                'sku'  => $this->variant->sku,
                'name' => $this->variant->name,
            ]),
        ];
    }
}
