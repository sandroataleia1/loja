<?php

declare(strict_types=1);

namespace App\Modules\Sync\Events;

use App\Modules\Sync\Models\SyncLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SyncBatchCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SyncLog $log,
        public readonly int     $syncedCount,
        public readonly int     $failedCount,
        public readonly int     $conflictCount,
    ) {}
}
