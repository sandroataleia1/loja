<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                => $this->uuid,
            'code'                => $this->code,
            'original_sale_id'    => $this->original_sale_id,
            'store_id'            => $this->store_id,
            'customer_id'         => $this->customer_id,
            'status'              => $this->status->value,
            'status_label'        => $this->status->label(),
            'return_type'         => $this->return_type,
            'reason'              => $this->reason?->value,
            'reason_label'        => $this->reason?->label(),
            'refund_amount_cents' => $this->refund_amount_cents,
            'refund_method'       => $this->refund_method,
            'stock_restocked'     => $this->stock_restocked,
            'financial_reversed'  => $this->financial_reversed,
            'notes'               => $this->notes,
            'processed_at'        => $this->processed_at?->toISOString(),
            'items'               => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'sale_item_id'       => $i->sale_item_id,
                'variant_id'         => $i->variant_id,
                'quantity_returned'  => $i->quantity_returned,
                'unit_price_cents'   => $i->unit_price_cents,
                'refund_amount_cents' => $i->refund_amount_cents,
                'condition'          => $i->condition,
                'condition_notes'    => $i->condition_notes,
            ])),
            'created_at'          => $this->created_at?->toISOString(),
        ];
    }
}
