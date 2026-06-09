<?php

declare(strict_types=1);

namespace App\Modules\Sync\DTOs;

use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class SyncBatchDTO extends BaseDTO
{
    /**
     * @param SyncOperationItemDTO[] $operations
     */
    public function __construct(
        public string $deviceUuid,
        public string $batchId,
        public array  $operations,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            deviceUuid: $request->string('device_uuid')->toString(),
            batchId:    $request->string('batch_id')->toString(),
            operations: array_map(
                fn (array $op) => SyncOperationItemDTO::fromArray($op),
                $request->array('operations'),
            ),
        );
    }
}
