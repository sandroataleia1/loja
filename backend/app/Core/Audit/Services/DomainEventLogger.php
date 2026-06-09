<?php

declare(strict_types=1);

namespace App\Core\Audit\Services;

use App\Core\Audit\DTOs\DomainEventDTO;
use App\Core\Audit\Models\DomainEventLog;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DomainEventLogger
{
    public function record(DomainEventDTO $dto): void
    {
        $correlationId = $dto->correlationId ?? CorrelationContext::getCorrelationId();

        try {
            DomainEventLog::create([
                'tenant_id'      => $dto->tenantId,
                'correlation_id' => $correlationId,
                'event_name'     => $dto->eventName,
                'payload'        => $dto->payload,
                'occurred_at'    => $dto->occurredAt ?? now(),
            ]);
        } catch (Throwable $e) {
            // Never let event logging failure break the caller
            Log::error('DomainEventLogger: failed to record domain event', [
                'event_name'     => $dto->eventName,
                'tenant_id'      => $dto->tenantId,
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
