<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Core\Auth\Models\ApprovalRequest
 */
final class ApprovalRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'tenant_id'        => $this->tenant_id,
            'operation_type'   => $this->operation_type->value,
            'operation_label'  => $this->operation_type->label(),
            'entity_type'      => $this->entity_type,
            'entity_uuid'      => $this->entity_uuid,
            'status'           => $this->status,
            'context'          => $this->context,
            'requester'        => $this->whenLoaded('requester', fn () => [
                'uuid' => $this->requester->uuid,
                'name' => $this->requester->name,
            ]),
            'resolver'         => $this->whenLoaded('resolver', fn () => $this->resolver ? [
                'uuid' => $this->resolver->uuid,
                'name' => $this->resolver->name,
            ] : null),
            'requested_at'     => $this->requested_at?->toIso8601String(),
            'resolved_at'      => $this->resolved_at?->toIso8601String(),
            'resolution_notes' => $this->resolution_notes,
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
