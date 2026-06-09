<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Jobs;

use App\Core\Audit\Services\CorrelationContext;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Analytics\Services\MetricsConsolidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recalcula CustomerMetrics do zero para um cliente específico.
 * Disparado quando a projeção incremental pode estar stale
 * (ex: após importação em lote, corrupção de dados, replay de eventos).
 */
final class ConsolidateCustomerMetricsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 2;
    public int $backoff = 30;

    private readonly string $correlationId;

    public function __construct(
        private readonly string $customerId,
        private readonly string $tenantId,
        string $correlationId = '',
    ) {
        $this->correlationId = $correlationId !== ''
            ? $correlationId
            : CorrelationContext::getCorrelationId();
    }

    public function handle(MetricsConsolidator $consolidator): void
    {
        CorrelationContext::setCorrelationId($this->correlationId);
        TenantContext::set($this->tenantId);

        try {
            $consolidator->consolidateCustomer($this->customerId, $this->tenantId);

            Log::info('ConsolidateCustomerMetricsJob: completed', [
                'customer_id'    => $this->customerId,
                'tenant_id'      => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('ConsolidateCustomerMetricsJob: failed', [
                'customer_id'    => $this->customerId,
                'tenant_id'      => $this->tenantId,
                'error'          => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        } finally {
            TenantContext::clear();
        }
    }
}
