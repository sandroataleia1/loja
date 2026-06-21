<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DocumentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'product_variant_id' => $this->product_variant_id,
            'name_snapshot'      => $this->name_snapshot,
            'sku_snapshot'       => $this->sku_snapshot,
            'unit_of_measure'    => $this->unit_of_measure,
            'quantity'           => (float) $this->quantity,
            'unit_price_cents'   => $this->unit_price_cents,
            'unit_price'         => $this->unit_price_cents / 100,
            'discount_cents'     => $this->discount_cents,
            'subtotal_cents'     => $this->subtotal_cents,
            'subtotal'           => $this->subtotal_cents / 100,
            'sort_order'         => $this->sort_order,
            'notes'              => $this->notes,
        ];
    }
}
