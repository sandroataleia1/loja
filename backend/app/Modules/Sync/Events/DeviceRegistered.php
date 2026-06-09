<?php

declare(strict_types=1);

namespace App\Modules\Sync\Events;

use App\Modules\Sync\Models\SyncDevice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DeviceRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SyncDevice $device,
    ) {}
}
