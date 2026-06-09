<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                  => $this->uuid,
            'origin_store_id'       => $this->origin_store_id,
            'destination_store_id'  => $this->destination_store_id,
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'notes'                 => $this->notes,
            'dispatched_at'         => $this->dispatched_at?->toISOString(),
            'received_at'           => $this->received_at?->toISOString(),
            'cancelled_at'          => $this->cancelled_at?->toISOString(),
            'origin_store'          => $this->whenLoaded('originStore',      fn () => new StoreResource($this->originStore)),
            'destination_store'     => $this->whenLoaded('destinationStore', fn () => new StoreResource($this->destinationStore)),
            'items'                 => $this->whenLoaded('items',            fn () => StockTransferItemResource::collection($this->items)),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
