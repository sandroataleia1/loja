<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Core\Auth\Models\Role
 */
final class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'is_system'   => $this->is_system,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
