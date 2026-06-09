<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                    => $this->uuid,
            'code'                    => $this->code,
            'status'                  => $this->status->value,
            'status_label'            => $this->status->label(),
            'order_date'              => $this->order_date?->toDateString(),
            'expected_delivery_date'  => $this->expected_delivery_date?->toDateString(),
            'subtotal'                => $this->subtotal,
            'discount'                => $this->discount,
            'total'                   => $this->total,
            'notes'                   => $this->notes,
            'supplier'                => $this->whenLoaded('supplier', fn () => [
                'uuid' => $this->supplier->uuid,
                'name' => $this->supplier->name,
                'code' => $this->supplier->code,
            ]),
            'items'                   => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'receipts_count'          => $this->whenLoaded('receipts', fn () => $this->receipts->count()),
            'created_at'              => $this->created_at?->toISOString(),
            'updated_at'              => $this->updated_at?->toISOString(),
        ];
    }
}
