<?php

declare(strict_types=1);

namespace App\Modules\Sync\Events;

use App\Modules\Sync\Models\SyncOperation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SyncConflictDetected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SyncOperation $operation,
        /** Motivo do conflito: 'backend_owned_entity' | 'concurrent_update' | etc. */
        public readonly string        $reason,
    ) {}
}
