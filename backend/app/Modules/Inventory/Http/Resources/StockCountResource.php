<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockCountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'store_id'     => $this->store_id,
            'status'       => $this->status->value,
            'status_label' => $this->status->label(),
            'notes'        => $this->notes,
            'started_at'   => $this->started_at?->toISOString(),
            'committed_at' => $this->committed_at?->toISOString(),
            'store'        => $this->whenLoaded('store',     fn () => new StoreResource($this->store)),
            'items'        => $this->whenLoaded('items',     fn () => StockCountItemResource::collection($this->items)),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
