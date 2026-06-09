<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Events;

use App\Core\Tenancy\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TenantCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
    ) {}
}
