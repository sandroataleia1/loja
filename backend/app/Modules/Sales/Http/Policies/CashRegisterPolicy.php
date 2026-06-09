<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Sales\Models\CashRegister;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CashRegisterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                            { return $user->hasPermission(PermissionEnum::SalesView); }
    public function view(User $user, CashRegister $register): bool       { return $user->hasPermission(PermissionEnum::SalesView); }
    public function create(User $user): bool                             { return $user->hasPermission(PermissionEnum::SettingsUpdate); }
    public function update(User $user, CashRegister $register): bool     { return $user->hasPermission(PermissionEnum::SettingsUpdate); }
    public function delete(User $user, CashRegister $register): bool     { return $user->hasPermission(PermissionEnum::SettingsUpdate); }
    public function withdrawal(User $user, CashRegister $register): bool { return $user->hasPermission(PermissionEnum::CashierOpen); }
    public function supply(User $user, CashRegister $register): bool     { return $user->hasPermission(PermissionEnum::CashierOpen); }
}
