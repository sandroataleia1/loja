<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SyncLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'device_id'        => $this->device_id,
            'batch_id'         => $this->batch_id,
            'direction'        => $this->direction,
            'operation_count'  => $this->operation_count,
            'synced_count'     => $this->synced_count,
            'failed_count'     => $this->failed_count,
            'conflict_count'   => $this->conflict_count,
            'duration_ms'      => $this->duration_ms,
            'success_rate'     => $this->successRate(),
            'request_summary'  => $this->request_summary,
            'response_summary' => $this->response_summary,
            'ip_address'       => $this->ip_address,
            'created_at'       => $this->created_at?->toISOString(),
            'completed_at'     => $this->completed_at?->toISOString(),
        ];
    }
}
