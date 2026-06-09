<?php

declare(strict_types=1);

namespace App\Core\Auth\Events;

use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RoleAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly TenantUser $tenantUser,
        public readonly Role       $role,
        public readonly ?string    $assignedBy,
    ) {}
}
