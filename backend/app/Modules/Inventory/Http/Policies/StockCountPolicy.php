<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Inventory\Models\StockCount;
use Illuminate\Auth\Access\HandlesAuthorization;

final class StockCountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                    { return $user->hasPermission(PermissionEnum::InventoryView); }
    public function view(User $user, StockCount $count): bool    { return $user->hasPermission(PermissionEnum::InventoryView); }
    public function create(User $user): bool                     { return $user->hasPermission(PermissionEnum::InventoryAdjust); }
    public function commit(User $user, StockCount $count): bool  { return $user->hasPermission(PermissionEnum::InventoryAdjust); }
}
