<?php

declare(strict_types=1);

namespace App\Modules\Sync\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Sync\Models\SyncDevice;
use App\Modules\Sync\Models\SyncLog;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Confirma recebimento de um pull pelo PDV.
 *
 * O checkpoint já foi avançado otimisticamente no SyncPullAction.
 * Este ack:
 * 1. Confirma que o device recebeu os dados (audit trail)
 * 2. Marca o SyncLog como confirmed
 * 3. Atualiza last_seen_at do device
 *
 * Se o PDV não enviar o ack (crash, timeout), o checkpoint avançado
 * é conservador: na próxima pull, a janela de tempo pode retornar
 * entidades extras (idempotente para o PDV).
 */
final readonly class AckPullAction
{
    public function execute(string $deviceUuid, string $batchId, string $pulledAt): SyncLog
    {
        $tenantId = TenantContext::getIdOrFail();

        $device = SyncDevice::where('tenant_id', $tenantId)
            ->where('device_uuid', $deviceUuid)
            ->where('is_active', true)
            ->first();

        if ($device === null) {
            throw new UnprocessableEntityHttpException(
                "Dispositivo '{$deviceUuid}' não encontrado ou inativo."
            );
        }

        $device->markSeen();

        $log = SyncLog::where('tenant_id', $tenantId)
            ->where('device_id', $device->uuid)
            ->where('batch_id', $batchId)
            ->where('direction', 'pull')
            ->first();

        if ($log === null) {
            Log::warning('sync.ack.log_not_found', [
                'device_uuid' => $deviceUuid,
                'batch_id'    => $batchId,
            ]);

            throw new UnprocessableEntityHttpException(
                "Log de pull não encontrado para batch '{$batchId}'."
            );
        }

        if (!$log->isCompleted()) {
            // Ack chegou antes do log ser completado (raro mas possível)
            $log->update(['completed_at' => now()]);
        }

        return $log;
    }
}
