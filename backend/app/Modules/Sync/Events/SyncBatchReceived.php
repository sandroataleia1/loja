<?php

declare(strict_types=1);

namespace App\Modules\Sync\Events;

use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SyncBatchReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SyncDevice $device,
        public readonly string     $batchId,
        public readonly int        $operationCount,
        public readonly SyncLog    $log,
    ) {}
}
