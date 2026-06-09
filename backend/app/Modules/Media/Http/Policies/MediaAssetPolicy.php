<?php

declare(strict_types=1);

namespace App\Modules\Media\Http\Policies;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\User;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Auth\Access\HandlesAuthorization;

final class MediaAssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool                    { return $user->hasPermission(PermissionEnum::ProductsView); }
    public function view(User $user, MediaAsset $asset): bool    { return $user->hasPermission(PermissionEnum::ProductsView); }
    public function create(User $user): bool                     { return $user->hasPermission(PermissionEnum::ProductsCreate); }
    public function update(User $user, MediaAsset $asset): bool  { return $user->hasPermission(PermissionEnum::ProductsUpdate); }
    public function delete(User $user, MediaAsset $asset): bool  { return $user->hasPermission(PermissionEnum::ProductsDelete); }
}
