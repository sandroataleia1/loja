<?php

declare(strict_types=1);

namespace App\Modules\Sync\Events;

use App\Modules\Sync\Enums\SyncOperationStatusEnum;
use App\Modules\Sync\Models\SyncOperation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SyncOperationProcessed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SyncOperation           $operation,
        public readonly SyncOperationStatusEnum $result,
        /** UUID da entidade criada/atualizada no backend, se aplicável. */
        public readonly ?string                 $entityUuid = null,
    ) {}
}
