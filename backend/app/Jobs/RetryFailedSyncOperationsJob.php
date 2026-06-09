<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Sync\Actions\ProcessSyncOperationAction;
use App\Modules\Sync\DTOs\SyncOperationItemDTO;
use App\Modules\Sync\Enums\SyncOperationStatusEnum;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job agendado para reprocessar operações de sync com falha.
 *
 * Executa a cada 5 minutos via scheduler.
 * Máximo de 3 tentativas por operação — após isso fica em 'failed' definitivo.
 * Respeita isolamento por tenant (processa por device → tenant).
 */
final class RetryFailedSyncOperationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('sync');
    }

    public function handle(ProcessSyncOperationAction $processOperation): void
    {
        $maxRetries = 3;

        SyncOperation::where('status', SyncOperationStatusEnum::Failed)
            ->where('retry_count', '<', $maxRetries)
            ->with('device')
            ->orderBy('received_at')
            ->chunk(100, function ($operations) use ($processOperation): void {
                foreach ($operations as $operation) {
                    $this->retryOperation($operation, $processOperation);
                }
            });
    }

    private function retryOperation(SyncOperation $operation, ProcessSyncOperationAction $action): void
    {
        $device = $operation->device;

        if ($device === null || !$device->is_active) {
            Log::info('sync.retry.skipped', [
                'operation_uuid' => $operation->operation_uuid,
                'reason'         => 'device_not_found_or_inactive',
            ]);
            return;
        }

        $tenantId = $operation->tenant_id;

        try {
            TenantContext::set($tenantId);

            $dto = new SyncOperationItemDTO(
                operationUuid:  $operation->operation_uuid,
                entityType:     $operation->entity_type,
                entityUuid:     $operation->entity_uuid,
                operationType:  $operation->operation_type,
                idempotencyKey: $operation->idempotency_key,
                payload:        $operation->payload ?? [],
                createdAt:      $operation->created_at->toIso8601String(),
            );

            // Reset para Processing antes de tentar novamente
            $operation->update(['status' => SyncOperationStatusEnum::Processing]);

            $action->execute($dto, $device, $operation->batch_id);

            Log::info('sync.retry.success', [
                'operation_uuid' => $operation->operation_uuid,
                'retry_count'    => $operation->retry_count + 1,
            ]);
        } catch (\Throwable $e) {
            Log::warning('sync.retry.failed', [
                'operation_uuid' => $operation->operation_uuid,
                'retry_count'    => $operation->retry_count + 1,
                'error'          => $e->getMessage(),
            ]);
        } finally {
            TenantContext::clear();
        }
    }
}
