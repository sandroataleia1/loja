<?php

declare(strict_types=1);

namespace App\Core\Auth\Events;

use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly Tenant $tenant,
    ) {}
}
