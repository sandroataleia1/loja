<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConditionalItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'variant_id'         => $this->variant_id,
            'quantity'           => $this->quantity,
            'unit_price_cents'   => $this->unit_price_cents,
            'returned_quantity'  => $this->returned_quantity,
            'sold_quantity'      => $this->sold_quantity,
            'pending_quantity'   => $this->pendingQuantity(),
            'total_cents'        => $this->totalCents(),
            'variant'            => $this->whenLoaded('variant', fn () => [
                'uuid' => $this->variant->uuid,
                'sku'  => $this->variant->sku,
                'name' => $this->variant->name,
            ]),
        ];
    }
}
