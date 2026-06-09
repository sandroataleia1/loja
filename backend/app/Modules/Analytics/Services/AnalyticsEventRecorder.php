<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Core\Audit\Services\CorrelationContext;
use App\Modules\Analytics\DTOs\AnalyticsEventDTO;
use App\Modules\Analytics\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AnalyticsEventRecorder
{
    /**
     * Persiste um evento no event store analítico.
     * Não lança exceção — falha silenciosa via log de erro para não bloquear caller.
     */
    public function record(AnalyticsEventDTO $dto): ?AnalyticsEvent
    {
        try {
            return AnalyticsEvent::create([
                'tenant_id'      => $dto->tenantId,
                'event_name'     => $dto->eventName,
                'aggregate_type' => $dto->aggregateType,
                'aggregate_uuid' => $dto->aggregateUuid,
                'payload'        => $dto->payload,
                'metadata'       => $dto->metadata,
                'correlation_id' => $dto->correlationId ?? CorrelationContext::getCorrelationId(),
                'occurred_at'    => $dto->occurredAt ?? now(),
            ]);
        } catch (Throwable $e) {
            Log::error('AnalyticsEventRecorder: failed to persist event', [
                'event_name'     => $dto->eventName,
                'aggregate_type' => $dto->aggregateType->value,
                'aggregate_uuid' => $dto->aggregateUuid,
                'tenant_id'      => $dto->tenantId,
                'error'          => $e->getMessage(),
            ]);

            return null;
        }
    }
}
