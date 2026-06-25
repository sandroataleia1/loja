<?php

declare(strict_types=1);

namespace App\Core\Audit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Core\Audit\Models\AuditLog
 */
final class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'tenant_id'      => $this->tenant_id,
            'store_id'       => $this->store_id,
            'user_id'        => $this->user_id,
            'entity_type'    => $this->entity_type?->value,
            'entity_uuid'    => $this->entity_uuid,
            'action'         => $this->action?->value,
            'action_label'   => $this->action?->label(),
            'is_high_risk'   => $this->action?->isHighRisk(),
            'old_values'     => $this->old_values,
            'new_values'     => $this->new_values,
            'metadata'       => $this->metadata,
            'ip'             => $this->ip,
            'user_agent'     => $this->user_agent,
            'correlation_id' => $this->correlation_id,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
