<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Auth\Access\HandlesAuthorization;

final class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::UsersView);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermission(PermissionEnum::UsersView)
            && $role->tenant_id === TenantContext::getId();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::UsersCreate);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission(PermissionEnum::UsersUpdate)
            && ! $role->isSystemRole()
            && $role->tenant_id === TenantContext::getId();
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission(PermissionEnum::UsersDelete)
            && ! $role->isSystemRole()
            && $role->tenant_id === TenantContext::getId();
    }

    public function syncPermissions(User $user, Role $role): bool
    {
        return $user->hasPermission(PermissionEnum::UsersUpdate)
            && ! $role->isSystemRole()
            && $role->tenant_id === TenantContext::getId();
    }
}
