<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SyncDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'store_id'     => $this->store_id,
            'device_uuid'  => $this->device_uuid,
            'name'         => $this->name,
            'platform'     => $this->platform,
            'app_version'  => $this->app_version,
            'is_active'    => $this->is_active,
            'is_online'    => $this->isOnline(),
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
