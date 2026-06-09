<?php

declare(strict_types=1);

namespace App\Modules\Sync\DTOs;

use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Shared\DTOs\BaseDTO;
use Illuminate\Http\Request;

final readonly class SyncPullDTO extends BaseDTO
{
    /**
     * @param SyncEntityTypeEnum[]        $entityTypes  entidades solicitadas pelo PDV
     * @param array<string, string>       $checkpoints  entity_type → last_synced_at (ISO 8601)
     */
    public function __construct(
        public string $deviceUuid,
        public string $batchId,
        public array  $entityTypes,
        public array  $checkpoints,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $entityTypes = array_map(
            fn (string $t) => SyncEntityTypeEnum::from($t),
            $request->array('entity_types'),
        );

        return new static(
            deviceUuid:  $request->string('device_uuid')->toString(),
            batchId:     $request->string('batch_id')->toString(),
            entityTypes: $entityTypes,
            checkpoints: $request->array('checkpoints') ?: [],
        );
    }
}
