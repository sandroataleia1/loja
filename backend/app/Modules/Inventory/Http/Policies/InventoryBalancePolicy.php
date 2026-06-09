<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class InventoryBalancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool { return $user->hasPermission(PermissionEnum::InventoryView); }
    public function adjust(User $user): bool  { return $user->hasPermission(PermissionEnum::InventoryAdjust); }
}
