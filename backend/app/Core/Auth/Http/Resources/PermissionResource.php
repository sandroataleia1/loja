<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Core\Auth\Models\Permission
 */
final class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'   => $this->uuid,
            'name'   => $this->name,
            'slug'   => $this->slug,
            'module' => $this->module,
        ];
    }
}
