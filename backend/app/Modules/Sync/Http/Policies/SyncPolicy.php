<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncLog;
use Illuminate\Auth\Access\HandlesAuthorization;

final class SyncPolicy
{
    use HandlesAuthorization;

    /** Qualquer usuário autenticado pode registrar dispositivos e sincronizar. */
    public function registerDevice(User $user): bool { return true; }
    public function push(User $user): bool           { return true; }
    public function pull(User $user): bool           { return true; }
    public function viewAnyDevice(User $user): bool  { return true; }

    public function viewDevice(User $user, SyncDevice $device): bool { return true; }

    public function deactivateDevice(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsUpdate);
    }

    public function viewAnyLog(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsView);
    }

    public function viewLog(User $user, SyncLog $log): bool
    {
        return $user->hasPermission(PermissionEnum::SettingsView);
    }
}
