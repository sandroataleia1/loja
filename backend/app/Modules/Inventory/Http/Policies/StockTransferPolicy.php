<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Inventory\Models\StockTransfer;
use Illuminate\Auth\Access\HandlesAuthorization;

final class StockTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                          { return $user->hasPermission(PermissionEnum::InventoryView); }
    public function view(User $user, StockTransfer $transfer): bool    { return $user->hasPermission(PermissionEnum::InventoryView); }
    public function create(User $user): bool                           { return $user->hasPermission(PermissionEnum::InventoryTransfer); }
    public function dispatch(User $user, StockTransfer $transfer): bool { return $user->hasPermission(PermissionEnum::InventoryTransfer); }
    public function receive(User $user, StockTransfer $transfer): bool  { return $user->hasPermission(PermissionEnum::InventoryTransfer); }
    public function cancel(User $user, StockTransfer $transfer): bool   { return $user->hasPermission(PermissionEnum::InventoryTransfer); }
}
