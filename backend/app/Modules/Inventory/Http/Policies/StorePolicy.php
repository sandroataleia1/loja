<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Inventory\Models\Store;
use Illuminate\Auth\Access\HandlesAuthorization;

final class StorePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                     { return true; }
    public function view(User $user, Store $store): bool          { return true; }
    public function create(User $user): bool                      { return $user->hasPermission(PermissionEnum::SettingsUpdate); }
    public function update(User $user, Store $store): bool        { return $user->hasPermission(PermissionEnum::SettingsUpdate); }
    public function delete(User $user, Store $store): bool        { return $user->hasPermission(PermissionEnum::SettingsUpdate); }
}
