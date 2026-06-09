<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Sales\Models\SaleReturn;
use Illuminate\Auth\Access\HandlesAuthorization;

final class SaleReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                      { return $user->hasPermission(PermissionEnum::SalesView); }
    public function view(User $user, SaleReturn $saleReturn): bool { return $user->hasPermission(PermissionEnum::SalesView); }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SalesCancel);
    }

    public function update(User $user, SaleReturn $saleReturn): bool
    {
        return $user->hasPermission(PermissionEnum::SalesCancel);
    }
}
