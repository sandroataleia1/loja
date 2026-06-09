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
 * Recalcula ProductMetrics do zero para um produto específico.
 * Inclui return_rate e stock_turnover que requerem joins complexos.
 */
final class ConsolidateProductMetricsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 2;
    public int $backoff = 30;

    private readonly string $correlationId;

    public function __construct(
        private readonly string $productId,
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
            $consolidator->consolidateProduct($this->productId, $this->tenantId);

            Log::info('ConsolidateProductMetricsJob: completed', [
                'product_id'     => $this->productId,
                'tenant_id'      => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('ConsolidateProductMetricsJob: failed', [
                'product_id'     => $this->productId,
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
