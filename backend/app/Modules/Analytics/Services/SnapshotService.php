<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Enums\MetricNameEnum;
use App\Modules\Analytics\Models\ChannelMetrics;
use App\Modules\Analytics\Models\CustomerMetrics;
use App\Modules\Analytics\Models\DailyMetricSnapshot;
use App\Modules\Analytics\Models\ProductMetrics;
use App\Modules\Analytics\Models\StoreMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Captura diária de métricas consolidadas para série temporal (DWH bridge).
 * Chamado pelo TakeDailySnapshotJob diariamente após meia-noite.
 * Upsert idempotente — pode ser re-executado sem duplicar dados.
 */
final class SnapshotService
{
    public function takeSnapshot(string $tenantId, ?Carbon $date = null): void
    {
        $snapshotDate = $date ?? today();

        $this->snapshotTenantLevel($tenantId, $snapshotDate);
        $this->snapshotChannelLevel($tenantId, $snapshotDate);
        $this->snapshotStoreLevel($tenantId, $snapshotDate);
        $this->updateDaysSinceLastPurchase($tenantId);
    }

    private function snapshotTenantLevel(string $tenantId, Carbon $date): void
    {
        try {
            $metrics = CustomerMetrics::query()
                ->where('tenant_id', $tenantId)
                ->selectRaw('
                    COALESCE(SUM(total_spent), 0)   AS total_revenue,
                    COALESCE(SUM(total_orders), 0)  AS total_orders,
                    COALESCE(AVG(average_ticket), 0) AS avg_ticket,
                    COUNT(*)                         AS active_customers_total
                ')
                ->first();

            $newCustomers = CustomerMetrics::query()
                ->where('tenant_id', $tenantId)
                ->where('total_orders', 1)
                ->count();

            $activeCustomers = CustomerMetrics::query()
                ->where('tenant_id', $tenantId)
                ->where('last_purchase_at', '>=', now()->subDays(30))
                ->count();

            $dormantCustomers = CustomerMetrics::query()
                ->where('tenant_id', $tenantId)
                ->where('days_since_last_purchase', '>=', 90)
                ->count();

            $snaps = [
                MetricNameEnum::TotalRevenue     => (float) ($metrics?->total_revenue ?? 0),
                MetricNameEnum::TotalOrders      => (float) ($metrics?->total_orders ?? 0),
                MetricNameEnum::AverageTicket    => (float) ($metrics?->avg_ticket ?? 0),
                MetricNameEnum::NewCustomers     => (float) $newCustomers,
                MetricNameEnum::ActiveCustomers  => (float) $activeCustomers,
                MetricNameEnum::DormantCustomers => (float) $dormantCustomers,
            ];

            foreach ($snaps as $metric => $value) {
                DailyMetricSnapshot::record($tenantId, $metric, $date, $value);
            }
        } catch (Throwable $e) {
            Log::error('SnapshotService: failed tenant-level snapshot', [
                'tenant_id' => $tenantId,
                'date'      => $date->toDateString(),
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function snapshotChannelLevel(string $tenantId, Carbon $date): void
    {
        try {
            $channels = ChannelMetrics::query()
                ->where('tenant_id', $tenantId)
                ->get(['channel_id', 'revenue', 'orders_count', 'average_ticket']);

            foreach ($channels as $channel) {
                $meta = ['channel_id' => $channel->channel_id];

                DailyMetricSnapshot::record($tenantId, MetricNameEnum::ChannelRevenue, $date, (float) $channel->revenue, $meta);
                DailyMetricSnapshot::record($tenantId, MetricNameEnum::ChannelOrders, $date, (float) $channel->orders_count, $meta);
                DailyMetricSnapshot::record($tenantId, MetricNameEnum::ChannelAvgTicket, $date, (float) $channel->average_ticket, $meta);
            }
        } catch (Throwable $e) {
            Log::error('SnapshotService: failed channel-level snapshot', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function snapshotStoreLevel(string $tenantId, Carbon $date): void
    {
        try {
            $stores = StoreMetrics::query()
                ->where('tenant_id', $tenantId)
                ->get(['store_id', 'revenue', 'sales_count', 'customer_count', 'inventory_value']);

            foreach ($stores as $store) {
                $meta = ['store_id' => $store->store_id];

                DailyMetricSnapshot::record($tenantId, MetricNameEnum::StoreRevenue, $date, (float) $store->revenue, $meta);
                DailyMetricSnapshot::record($tenantId, MetricNameEnum::StoreSales, $date, (float) $store->sales_count, $meta);
                DailyMetricSnapshot::record($tenantId, MetricNameEnum::StoreCustomers, $date, (float) $store->customer_count, $meta);
                DailyMetricSnapshot::record($tenantId, MetricNameEnum::TotalInventoryValue, $date, (float) $store->inventory_value, $meta);
            }
        } catch (Throwable $e) {
            Log::error('SnapshotService: failed store-level snapshot', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /** Atualiza days_since_last_purchase para todos os clientes do tenant (batch). */
    private function updateDaysSinceLastPurchase(string $tenantId): void
    {
        try {
            // Uma única query UPDATE em vez de N updates individuais
            DB::table('customer_metrics')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('last_purchase_at')
                ->update([
                    'days_since_last_purchase' => DB::raw(
                        "EXTRACT(DAY FROM AGE(NOW(), last_purchase_at))::int"
                    ),
                    'updated_at' => now(),
                ]);
        } catch (Throwable $e) {
            Log::error('SnapshotService: failed to update days_since_last_purchase', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}
