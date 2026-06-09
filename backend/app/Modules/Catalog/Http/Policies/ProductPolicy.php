<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Catalog\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool  { return $user->hasPermission(PermissionEnum::ProductsView); }
    public function view(User $user, Product $product): bool { return $user->hasPermission(PermissionEnum::ProductsView); }
    public function create(User $user): bool   { return $user->hasPermission(PermissionEnum::ProductsCreate); }
    public function update(User $user, Product $product): bool { return $user->hasPermission(PermissionEnum::ProductsUpdate); }
    public function delete(User $user, Product $product): bool { return $user->hasPermission(PermissionEnum::ProductsDelete); }
    public function restore(User $user, Product $product): bool { return $user->hasPermission(PermissionEnum::ProductsUpdate); }
}
