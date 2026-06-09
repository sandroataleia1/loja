<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Omnichannel\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

final class OrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SalesView);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission(PermissionEnum::SalesView);
    }

    public function create(User $user): bool
    {
        return true;  // any authenticated user can place an order (via channel)
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasPermission(PermissionEnum::SalesCancel);
    }

    public function fulfill(User $user, Order $order): bool
    {
        return $user->hasPermission(PermissionEnum::SalesView);
    }
}
