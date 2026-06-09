<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'purchase_order_id' => $this->purchase_order_id,
            'status'            => $this->status->value,
            'status_label'      => $this->status->label(),
            'received_at'       => $this->received_at?->toISOString(),
            'notes'             => $this->notes,
            'received_by'       => $this->whenLoaded('receivedBy', fn () => [
                'uuid' => $this->receivedBy->uuid,
                'name' => $this->receivedBy->name,
            ]),
            'items'             => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'uuid'              => $i->uuid,
                'product_variant_id'=> $i->product_variant_id,
                'quantity_received' => $i->quantity_received,
                'unit_cost'         => $i->unit_cost,
                'variant'           => ['uuid' => $i->variant?->uuid, 'sku' => $i->variant?->sku],
            ])),
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
