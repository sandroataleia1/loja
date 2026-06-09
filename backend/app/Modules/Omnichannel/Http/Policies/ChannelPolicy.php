<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Omnichannel\Models\Channel;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ChannelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsView)
            || $user->hasPermission(PermissionEnum::ProductsView);
    }

    public function view(User $user, Channel $channel): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsUpdate);
    }

    public function update(User $user, Channel $channel): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsUpdate);
    }

    public function delete(User $user, Channel $channel): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsUpdate);
    }

    public function publish(User $user, Channel $channel): bool
    {
        return $user->hasPermission(PermissionEnum::ProductsUpdate);
    }

    public function manageCredentials(User $user, Channel $channel): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsUpdate);
    }
}
