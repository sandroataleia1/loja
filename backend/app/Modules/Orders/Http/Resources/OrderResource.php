<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                  => $this->uuid,
            'number'                => $this->number,
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'status_color'          => $this->status->color(),
            'store_id'              => $this->store_id,
            'customer_id'           => $this->customer_id,
            'customer'              => $this->whenLoaded('customer', fn () => [
                'uuid'  => $this->customer->uuid,
                'name'  => $this->customer->name,
                'phone' => $this->customer->phone ?? null,
                'email' => $this->customer->email ?? null,
            ]),
            'quote_id'              => $this->quote_id,
            'sale_id'               => $this->sale_id,
            'discount_type'         => $this->discount_type,
            'discount_value'        => (float) $this->discount_value,
            'discount_cents'        => $this->discount_cents,
            'discount'              => $this->discount_cents / 100,
            'subtotal_cents'        => $this->subtotal_cents,
            'subtotal'              => $this->subtotal_cents / 100,
            'total_cents'           => $this->total_cents,
            'total'                 => $this->total_cents / 100,
            'notes'                 => $this->notes,
            'internal_notes'        => $this->internal_notes,
            'payment_terms'         => $this->payment_terms,
            'expected_at'           => $this->expected_at?->toDateString(),
            'confirmed_at'          => $this->confirmed_at?->toISOString(),
            'delivered_at'          => $this->delivered_at?->toISOString(),
            'completed_at'          => $this->completed_at?->toISOString(),
            'cancelled_at'          => $this->cancelled_at?->toISOString(),
            'cancellation_reason'   => $this->cancellation_reason,
            'allowed_transitions'   => collect($this->status->allowedTransitions())
                                            ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])
                                            ->values(),
            'items'                 => $this->whenLoaded('items', fn () => DocumentItemResource::collection($this->items)),
            'items_count'           => $this->whenCounted('items'),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
