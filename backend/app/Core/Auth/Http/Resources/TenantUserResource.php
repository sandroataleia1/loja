<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Core\Auth\Models\TenantUser
 */
final class TenantUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'tenant_id'   => $this->tenant_id,
            'user_id'     => $this->user_id,
            'name'        => $this->whenLoaded('user', fn () => $this->user->name),
            'email'       => $this->whenLoaded('user', fn () => $this->user->email),
            'username'    => $this->whenLoaded('user', fn () => $this->user->username),
            'phone'       => $this->whenLoaded('user', fn () => $this->user->phone),
            'has_pin'     => $this->whenLoaded('user', fn () => $this->user->pin !== null),
            'role'        => new RoleResource($this->whenLoaded('role')),
            'is_active'   => $this->is_active,
            'joined_at'   => $this->joined_at?->toIso8601String(),
            'store_ids'   => $this->whenLoaded('storeAccesses', fn () =>
                $this->storeAccesses->pluck('store_id')->values(),
            ),
        ];
    }
}
