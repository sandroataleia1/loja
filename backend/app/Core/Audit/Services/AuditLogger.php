<?php

declare(strict_types=1);

namespace App\Core\Audit\Services;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AuditLogger
{
    public function record(AuditLogDTO $dto): void
    {
        $correlationId = $dto->correlationId ?? CorrelationContext::getCorrelationId();

        try {
            AuditLog::create([
                'tenant_id'      => $dto->tenantId,
                'store_id'       => $dto->storeId,
                'user_id'        => $dto->userId,
                'device_id'      => $dto->deviceId,
                'correlation_id' => $correlationId,
                'entity_type'    => $dto->entityType,
                'entity_uuid'    => $dto->entityUuid,
                'action'         => $dto->action,
                'old_values'     => $dto->oldValues,
                'new_values'     => $dto->newValues,
                'metadata'       => $dto->metadata,
                'ip'             => $dto->ip,
                'user_agent'     => $dto->userAgent,
            ]);

            Log::channel('audit')->info($dto->action->value, [
                'entity_type'    => $dto->entityType->value,
                'entity_uuid'    => $dto->entityUuid,
                'tenant_id'      => $dto->tenantId,
                'user_id'        => $dto->userId,
                'correlation_id' => $correlationId,
                'high_risk'      => $dto->action->isHighRisk(),
            ]);
        } catch (Throwable $e) {
            // Never let audit failure break the caller
            Log::error('AuditLogger: failed to record audit entry', [
                'action'         => $dto->action->value,
                'entity_type'    => $dto->entityType->value,
                'entity_uuid'    => $dto->entityUuid,
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
