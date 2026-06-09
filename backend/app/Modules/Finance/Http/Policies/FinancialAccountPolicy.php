<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Finance\Models\FinancialAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

final class FinancialAccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                           { return $user->hasPermission(PermissionEnum::FinancialView); }
    public function view(User $user, FinancialAccount $account): bool   { return $user->hasPermission(PermissionEnum::FinancialView); }
    public function create(User $user): bool                            { return $user->hasPermission(PermissionEnum::FinancialCreate); }
    public function update(User $user, FinancialAccount $account): bool { return $user->hasPermission(PermissionEnum::FinancialUpdate); }
    public function delete(User $user, FinancialAccount $account): bool { return $user->hasPermission(PermissionEnum::FinancialDelete); }
}
