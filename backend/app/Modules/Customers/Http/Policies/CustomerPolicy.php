<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Customers\Models\Customer;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::CustomersView);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermission(PermissionEnum::CustomersView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::CustomersCreate);
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($customer->is_default_consumer) {
            return false;
        }

        return $user->hasPermission(PermissionEnum::CustomersUpdate);
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($customer->is_default_consumer) {
            return false;
        }

        return $user->hasPermission(PermissionEnum::CustomersDelete);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->hasPermission(PermissionEnum::CustomersUpdate);
    }
}
