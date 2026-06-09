<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConditionalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'code'           => $this->code,
            'status'         => $this->status->value,
            'status_label'   => $this->status->label(),
            'expires_at'     => $this->expires_at?->toISOString(),
            'due_date'       => $this->expires_at?->toDateString(),
            'is_overdue'     => $this->isOverdue(),
            'subtotal_cents' => $this->subtotal_cents,
            'total_cents'    => $this->total_cents,
            'total_amount'   => $this->total_cents / 100,
            'total_items'    => $this->total_items,
            'notes'          => $this->notes,
            'customer'       => $this->whenLoaded('customer', fn () => [
                'uuid' => $this->customer->uuid,
                'name' => $this->customer->name,
                'code' => $this->customer->code,
            ]),
            'store'          => $this->whenLoaded('store', fn () => [
                'uuid' => $this->store->uuid,
                'name' => $this->store->name,
            ]),
            'items'          => ConditionalItemResource::collection($this->whenLoaded('items')),
            'status_history' => ConditionalStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
