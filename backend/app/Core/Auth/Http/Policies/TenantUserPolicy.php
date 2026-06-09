<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class TenantUserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::UsersView);
    }

    public function view(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission(PermissionEnum::UsersView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::UsersCreate);
    }

    public function update(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission(PermissionEnum::UsersUpdate);
    }

    public function delete(User $user, TenantUser $tenantUser): bool
    {
        return $user->hasPermission(PermissionEnum::UsersDelete);
    }
}
