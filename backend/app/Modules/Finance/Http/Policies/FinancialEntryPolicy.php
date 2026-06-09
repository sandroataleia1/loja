<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Finance\Models\FinancialEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

final class FinancialEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                          { return $user->hasPermission(PermissionEnum::FinancialView); }
    public function view(User $user, FinancialEntry $entry): bool      { return $user->hasPermission(PermissionEnum::FinancialView); }
    public function create(User $user): bool                           { return $user->hasPermission(PermissionEnum::FinancialCreate); }
    public function pay(User $user, FinancialEntry $entry): bool       { return $user->hasPermission(PermissionEnum::FinancialCreate); }
    public function cancel(User $user, FinancialEntry $entry): bool    { return $user->hasPermission(PermissionEnum::FinancialDelete); }
}
