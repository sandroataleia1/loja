<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Listeners;

use App\Modules\Inventory\Events\StockAdjusted;

/**
 * Stub — sincroniza snapshots de saldo com pipeline de analytics/BI.
 * Implementação futura: publicar evento em fila 'reports' para data warehouse.
 */
final class SyncBalanceToAnalytics
{
    public function handle(StockAdjusted $event): void
    {
        // TODO: dispatch SyncInventorySnapshotJob via queue 'reports'
    }
}
