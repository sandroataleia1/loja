<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Conditional\Models\Conditional;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ConditionalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SalesView);
    }

    public function view(User $user, Conditional $conditional): bool
    {
        return $user->hasPermission(PermissionEnum::SalesView);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionEnum::SalesCreate);
    }

    public function update(User $user, Conditional $conditional): bool
    {
        return ! $conditional->status->isSettled()
            && $user->hasPermission(PermissionEnum::SalesCreate);
    }

    public function delete(User $user, Conditional $conditional): bool
    {
        return $conditional->status->canCancel()
            && $user->hasPermission(PermissionEnum::SalesCancel);
    }
}
