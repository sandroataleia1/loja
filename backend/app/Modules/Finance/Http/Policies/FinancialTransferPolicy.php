<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Finance\Models\FinancialTransfer;
use Illuminate\Auth\Access\HandlesAuthorization;

final class FinancialTransferPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                           { return $user->hasPermission(PermissionEnum::FinancialView); }
    public function view(User $user, FinancialTransfer $transfer): bool { return $user->hasPermission(PermissionEnum::FinancialView); }
    public function create(User $user): bool                            { return $user->hasPermission(PermissionEnum::FinancialCreate); }
}
