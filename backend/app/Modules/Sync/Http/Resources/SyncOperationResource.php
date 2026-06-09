<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SyncOperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'operation_uuid'  => $this->operation_uuid,
            'device_id'       => $this->device_id,
            'entity_type'     => $this->entity_type->value,
            'entity_uuid'     => $this->entity_uuid,
            'operation_type'  => $this->operation_type->value,
            'batch_id'        => $this->batch_id,
            'status'          => $this->status->value,
            'status_label'    => $this->status->label(),
            'idempotency_key' => $this->idempotency_key,
            'retry_count'     => $this->retry_count,
            'last_error'      => $this->last_error,
            'created_at'      => $this->created_at?->toISOString(),
            'received_at'     => $this->received_at?->toISOString(),
            'processed_at'    => $this->processed_at?->toISOString(),
        ];
    }
}
