<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Sales\Models\CashRegisterSession;
use Illuminate\Auth\Access\HandlesAuthorization;

final class CashRegisterSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                            { return $user->hasPermission(PermissionEnum::SalesView); }
    public function view(User $user, CashRegisterSession $session): bool { return $user->hasPermission(PermissionEnum::SalesView); }
    public function open(User $user): bool                               { return $user->hasPermission(PermissionEnum::CashierOpen); }

    public function close(User $user, CashRegisterSession $session): bool
    {
        // O próprio operador que abriu, ou qualquer um com permissão de fechar caixa
        return $user->uuid === $session->user_id
            || $user->hasPermission(PermissionEnum::CashierClose);
    }
}
