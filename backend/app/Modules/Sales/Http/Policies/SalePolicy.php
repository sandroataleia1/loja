<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Sales\Models\Sale;
use Illuminate\Auth\Access\HandlesAuthorization;

final class SalePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                { return $user->hasPermission(PermissionEnum::SalesView); }
    public function view(User $user, Sale $sale): bool       { return $user->hasPermission(PermissionEnum::SalesView); }
    public function create(User $user): bool                 { return $user->hasPermission(PermissionEnum::SalesCreate); }
    public function complete(User $user, Sale $sale): bool   { return $user->hasPermission(PermissionEnum::SalesCreate); }
    public function addPayment(User $user, Sale $sale): bool { return $user->hasPermission(PermissionEnum::SalesCreate); }

    public function applyDiscount(User $user, Sale $sale): bool
    {
        return $user->hasPermission(PermissionEnum::SalesDiscount);
    }

    public function cancel(User $user, Sale $sale): bool
    {
        if ($sale->status->stockWasDecremented()) {
            return $user->hasPermission(PermissionEnum::SalesCancel);
        }

        return $user->hasPermission(PermissionEnum::SalesCreate);
    }

    public function addCommission(User $user, Sale $sale): bool
    {
        return $user->hasPermission(PermissionEnum::SalesCreate);
    }
}
