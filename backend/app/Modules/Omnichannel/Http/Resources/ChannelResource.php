<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'                  => $this->uuid,
            'code'                  => $this->code,
            'name'                  => $this->name,
            'type'                  => $this->type->value,
            'type_label'            => $this->type->label(),
            'is_active'             => $this->is_active,
            'store_id'              => $this->store_id,
            'requires_credentials'  => $this->type->requiresCredentials(),
            'is_async_channel'      => $this->type->isAsyncChannel(),
            'metadata'              => $this->metadata,
            'created_at'            => $this->created_at?->toISOString(),
        ];
    }
}
