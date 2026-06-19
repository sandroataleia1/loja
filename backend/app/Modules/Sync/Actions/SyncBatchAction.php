<?php

declare(strict_types=1);

namespace App\Modules\Sync\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Sync\DTOs\SyncBatchDTO;
use App\Modules\Sync\Enums\SyncOperationStatusEnum;
use App\Modules\Sync\Events\SyncBatchCompleted;
use App\Modules\Sync\Events\SyncBatchReceived;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Processa um batch de operações enviado pelo PDV (push).
 *
 * Garante:
 * - Validação do dispositivo (tenant + store scope)
 * - Criação do SyncLog antes de processar
 * - Idempotência por operação via ProcessSyncOperationAction
 * - batch_id injetado na criação (sem UPDATE separado)
 * - Eventos SyncBatchReceived + SyncBatchCompleted
 */
final readonly class SyncBatchAction
{
    public function __construct(
        private ProcessSyncOperationAction $processOperation,
    ) {}

    /**
     * @return array{
     *   log: SyncLog,
     *   results: array<string, array{status: string, entity_uuid: ?string}>
     * }
     */
    public function execute(SyncBatchDTO $dto, Request $request): array
    {
        $tenantId  = TenantContext::getIdOrFail();
        $startedAt = microtime(true);

        $device = $this->resolveDevice($dto->deviceUuid, $tenantId);
        $device->markSeen();

        $operationCount = count($dto->operations);

        $log = SyncLog::create([
            'tenant_id'       => $tenantId,
            'device_id'       => $device->uuid,
            'batch_id'        => $dto->batchId,
            'direction'       => 'push',
            'operation_count' => $operationCount,
            'synced_count'    => 0,
            'failed_count'    => 0,
            'conflict_count'  => 0,
            'request_summary' => $this->buildRequestSummary($dto),
            'ip_address'      => $request->ip(),
            'created_at'      => now(),
        ]);

        SyncBatchReceived::dispatch($device, $dto->batchId, $operationCount, $log);

        $results  = [];
        $synced   = 0;
        $failed   = 0;
        $conflict = 0;

        foreach ($dto->operations as $operationDto) {
            // batch_id é passado diretamente — sem UPDATE separado após INSERT
            $result = $this->processOperation->execute($operationDto, $device, $dto->batchId);

            $results[$operationDto->operationUuid] = [
                'status'      => $result['status']->value,
                'entity_uuid' => $result['entity_uuid'],
            ];

            match ($result['status']) {
                SyncOperationStatusEnum::Synced   => $synced++,
                SyncOperationStatusEnum::Failed   => $failed++,
                SyncOperationStatusEnum::Conflict => $conflict++,
                default                           => null,
            };
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $log->update([
            'synced_count'     => $synced,
            'failed_count'     => $failed,
            'conflict_count'   => $conflict,
            'response_summary' => $results,
            'completed_at'     => now(),
            'duration_ms'      => $durationMs,
        ]);

        SyncBatchCompleted::dispatch($log, $synced, $failed, $conflict);

        return ['log' => $log, 'results' => $results];
    }

    private function resolveDevice(string $deviceUuid, string $tenantId): SyncDevice
    {
        $device = SyncDevice::where('tenant_id', $tenantId)
            ->where('device_uuid', $deviceUuid)
            ->where('is_active', true)
            ->first();

        if ($device === null) {
            throw new UnprocessableEntityHttpException(
                "Dispositivo '{$deviceUuid}' não registrado ou inativo nesta empresa."
            );
        }

        return $device;
    }

    /** @return array<string, mixed> */
    private function buildRequestSummary(SyncBatchDTO $dto): array
    {
        $typeCounts = [];
        foreach ($dto->operations as $op) {
            $key = $op->entityType->value . '.' . $op->operationType->value;
            $typeCounts[$key] = ($typeCounts[$key] ?? 0) + 1;
        }

        return [
            'batch_id'    => $dto->batchId,
            'total'       => count($dto->operations),
            'type_counts' => $typeCounts,
        ];
    }
}
