<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Fiscal\Models\TenantFiscalSettings;
use Illuminate\Auth\Access\HandlesAuthorization;

final class FiscalSettingsPolicy
{
    use HandlesAuthorization;

    public function view(User $user): bool                                    { return $user->hasPermission(PermissionEnum::FiscalView); }
    public function upsert(User $user): bool                                  { return $user->hasPermission(PermissionEnum::SettingsUpdate); }
    public function viewAny(User $user, TenantFiscalSettings $settings): bool { return $user->hasPermission(PermissionEnum::FiscalView); }
}
