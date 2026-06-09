<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                  => $this->uuid,
            'product_variant_id'    => $this->product_variant_id,
            'sku_snapshot'          => $this->sku_snapshot,
            'name_snapshot'         => $this->name_snapshot,
            'attributes_snapshot'   => $this->attributes_snapshot,
            'quantity'              => $this->quantity,
            'unit_price_cents'      => $this->unit_price_cents,
            'cost_price_cents'      => $this->cost_price_cents,
            'discount_amount_cents' => $this->discount_amount_cents,
            'subtotal_cents'        => $this->subtotal_cents,
            'total_cents'           => $this->total_cents,
            'discounts'             => $this->whenLoaded('discounts', fn () => SaleDiscountResource::collection($this->discounts)),
        ];
    }
}
