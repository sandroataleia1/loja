<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Resources;

use App\Core\Auth\Services\TenantAuthorizationService;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tenantId       = TenantContext::getId();
        $permissionSet  = $tenantId
            ? app(TenantAuthorizationService::class)->buildPermissionSet($this->uuid, $tenantId)
            : null;

        return [
            'uuid'           => $this->uuid,
            'name'           => $this->name,
            'email'          => $this->email,
            'is_active'      => $this->is_active,
            'permission_set' => $permissionSet?->toArray(),
        ];
    }
}
