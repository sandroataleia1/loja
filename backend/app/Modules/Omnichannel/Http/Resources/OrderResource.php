<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'order_number' => $this->order_number,
            'channel_id'   => $this->channel_id,
            'customer_id'  => $this->customer_id,
            'store_id'     => $this->store_id,
            'status'       => $this->status->value,
            'status_label' => $this->status->label(),
            'is_final'     => $this->status->isFinal(),
            'total_amount' => $this->total_amount,
            'placed_at'    => $this->placed_at->toISOString(),
            'metadata'     => $this->metadata,
            'channel'      => ChannelResource::make($this->whenLoaded('channel')),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
