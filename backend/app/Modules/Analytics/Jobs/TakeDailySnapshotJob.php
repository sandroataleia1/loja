<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Jobs;

use App\Core\Audit\Services\CorrelationContext;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Analytics\Services\SnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Captura diária de métricas para série temporal (DWH bridge).
 * Agendado via console.php — dispara um job por tenant ativo.
 * Idempotente: pode ser re-executado sem duplicar snapshots.
 */
final class TakeDailySnapshotJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 3;
    public int $backoff = 120;

    private readonly string $correlationId;

    public function __construct(
        private readonly string $tenantId,
        string $correlationId = '',
    ) {
        $this->correlationId = $correlationId !== ''
            ? $correlationId
            : CorrelationContext::getCorrelationId();
    }

    public function handle(SnapshotService $snapshotService): void
    {
        CorrelationContext::setCorrelationId($this->correlationId);
        TenantContext::set($this->tenantId);

        try {
            $snapshotService->takeSnapshot($this->tenantId);

            Log::info('TakeDailySnapshotJob: completed', [
                'tenant_id'      => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('TakeDailySnapshotJob: failed', [
                'tenant_id'      => $this->tenantId,
                'error'          => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            throw $e;
        } finally {
            TenantContext::clear();
        }
    }

    /**
     * Dispatcher estático: lê todos os tenants ativos e despacha um job por tenant.
     * Chamado pelo agendador em routes/console.php.
     */
    public static function dispatchForAllTenants(): void
    {
        DB::table('tenants')
            ->where('is_active', true)
            ->pluck('uuid')
            ->each(fn (string $tenantId) => static::dispatch($tenantId));
    }
}
