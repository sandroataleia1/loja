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
            'uuid'              => $this->uuid,
            'name'              => $this->name,
            'username'          => $this->username,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'avatar_url'        => $this->avatar_url,
            'preferences'       => $this->preferences ?? [],
            'is_active'         => $this->is_active,
            'has_pin'           => $this->pin !== null,
            'last_login_at'     => $this->last_login_at?->toIso8601String(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'permission_set'    => $permissionSet?->toArray(),
        ];
    }
}
