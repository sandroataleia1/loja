<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ChannelProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'channel_id'         => $this->channel_id,
            'product_id'         => $this->product_id,
            'is_published'       => $this->is_published,
            'published_at'       => $this->published_at?->toISOString(),
            'external_reference' => $this->external_reference,
            'sync_status'        => $this->sync_status->value,
            'sync_status_label'  => $this->sync_status->label(),
            'needs_sync'         => $this->sync_status->needsSync(),
            'metadata'           => $this->metadata,
            'channel'            => ChannelResource::make($this->whenLoaded('channel')),
        ];
    }
}
