<?php

declare(strict_types=1);

namespace App\Modules\Sync\DTOs;

use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Enums\SyncOperationTypeEnum;

/**
 * Value-object aninhado de uma operação dentro de um SyncBatch.
 *
 * NÃO estende BaseDTO: é sempre construído via fromArray() a partir do payload
 * do lote (SyncBatchDTO::fromRequest), nunca diretamente de uma Request — logo
 * o contrato fromRequest() de BaseDTO não se aplica.
 */
final readonly class SyncOperationItemDTO
{
    public function __construct(
        public string               $operationUuid,
        public SyncEntityTypeEnum   $entityType,
        public string               $entityUuid,
        public SyncOperationTypeEnum $operationType,
        public string               $idempotencyKey,
        public array                $payload,
        /** ISO 8601 timestamp do dispositivo no momento da operação. */
        public string               $createdAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            operationUuid:  $data['operation_uuid'],
            entityType:     SyncEntityTypeEnum::from($data['entity_type']),
            entityUuid:     $data['entity_uuid'],
            operationType:  SyncOperationTypeEnum::from($data['operation_type']),
            idempotencyKey: $data['idempotency_key'],
            payload:        $data['payload'] ?? [],
            createdAt:      $data['created_at'],
        );
    }
}
