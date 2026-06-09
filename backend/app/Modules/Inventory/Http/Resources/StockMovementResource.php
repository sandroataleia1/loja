<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'store_id'        => $this->store_id,
            'variant_id'      => $this->variant_id,
            'type'            => $this->type->value,
            'type_label'      => $this->type->label(),
            'quantity'        => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after'  => $this->quantity_after,
            'reserved_before' => $this->reserved_before,
            'reserved_after'  => $this->reserved_after,
            'reference_type'  => $this->reference_type,
            'reference_id'    => $this->reference_id,
            'notes'           => $this->notes,
            'metadata'        => $this->metadata,
            'created_by'      => $this->created_by,
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
