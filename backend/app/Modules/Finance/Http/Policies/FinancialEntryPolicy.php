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

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::FinancialView)
            || $user->hasPermission(PermissionEnum::FinancialAccountsReceivable)
            || $user->hasPermission(PermissionEnum::FinancialAccountsPayable);
    }

    private function canWrite(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::FinancialCreate)
            || $user->hasPermission(PermissionEnum::FinancialAccountsReceivable)
            || $user->hasPermission(PermissionEnum::FinancialAccountsPayable);
    }

    public function viewAny(User $user): bool                          { return $this->canView($user); }
    public function view(User $user, FinancialEntry $entry): bool      { return $this->canView($user); }
    public function create(User $user): bool                           { return $this->canWrite($user); }
    public function pay(User $user, FinancialEntry $entry): bool       { return $this->canWrite($user); }
    public function cancel(User $user, FinancialEntry $entry): bool    { return $user->hasPermission(PermissionEnum::FinancialDelete) || $user->hasPermission(PermissionEnum::FinancialAccountsPayable); }
}
