<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                   => $this->uuid,
            'number'                 => $this->number,
            'status'                 => $this->status->value,
            'status_label'           => $this->status->label(),
            'status_color'           => $this->status->color(),
            'store_id'               => $this->store_id,
            'customer_id'            => $this->customer_id,
            'customer'               => $this->whenLoaded('customer', fn () => [
                'uuid'  => $this->customer->uuid,
                'name'  => $this->customer->name,
                'phone' => $this->customer->phone ?? null,
                'email' => $this->customer->email ?? null,
            ]),
            'seller_id'              => $this->seller_id,
            'seller'                 => $this->whenLoaded('seller', fn () => $this->seller
                ? ['uuid' => $this->seller->uuid, 'name' => $this->seller->name]
                : null
            ),
            'validity_days'          => $this->validity_days,
            'valid_until'            => $this->valid_until?->toDateString(),
            'is_expired'             => $this->isExpired(),
            'discount_type'          => $this->discount_type,
            'discount_value'         => (float) $this->discount_value,
            'discount_cents'         => $this->discount_cents,
            'discount'               => $this->discount_cents / 100,
            'subtotal_cents'         => $this->subtotal_cents,
            'subtotal'               => $this->subtotal_cents / 100,
            'total_cents'            => $this->total_cents,
            'total'                  => $this->total_cents / 100,
            'notes'                  => $this->notes,
            'internal_notes'         => $this->internal_notes,
            'payment_terms'          => $this->payment_terms,
            'payment_method_id'      => $this->payment_method_id,
            'payment_condition_id'   => $this->payment_condition_id,
            'converted_to_order_id'  => $this->converted_to_order_id,
            'converted_at'           => $this->converted_at?->toISOString(),
            'sent_at'                => $this->sent_at?->toISOString(),
            'viewed_at'              => $this->viewed_at?->toISOString(),
            'items'                  => $this->whenLoaded('items', fn () => DocumentItemResource::collection($this->items)),
            'items_count'            => $this->whenCounted('items'),
            'created_at'             => $this->created_at?->toISOString(),
            'updated_at'             => $this->updated_at?->toISOString(),
        ];
    }
}
