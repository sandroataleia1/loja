<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Modules\Analytics\Models\ChannelMetrics;
use App\Modules\Analytics\Models\CustomerMetrics;
use App\Modules\Analytics\Models\ProductMetrics;
use App\Modules\Analytics\Models\StoreMetrics;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Models\SaleReturn;
use App\Modules\Sales\Models\SaleReturnItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recalcula projeções de métricas a partir do zero usando dados de Sales.
 * Caminho "pesado" — usado pelos jobs de consolidação ou quando projeções ficam stale.
 * Para atualizações incrementais, use os listeners diretamente.
 */
final class MetricsConsolidator
{
    public function __construct(
        private readonly MetricsCalculator $calculator,
    ) {}

    public function consolidateCustomer(string $customerId, string $tenantId): CustomerMetrics
    {
        $sales = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->whereIn('status', ['completed'])
            ->orderBy('completed_at')
            ->get(['uuid', 'total_cents', 'completed_at']);

        $totalOrders = $sales->count();
        $totalSpent  = $this->calculator->centsToDecimal($sales->sum('total_cents'));
        $avgTicket   = $this->calculator->averageTicket($totalOrders, $totalSpent);

        $lastSale       = $sales->last();
        $lastPurchaseAt = $lastSale?->completed_at;

        $purchaseDates = $sales
            ->filter(fn ($s) => $s->completed_at !== null)
            ->map(fn ($s) => Carbon::instance($s->completed_at))
            ->values()
            ->all();

        $frequency         = $this->calculator->purchaseFrequency($purchaseDates);
        $daysSinceLast     = $this->calculator->daysSinceLastPurchase($lastPurchaseAt);

        return CustomerMetrics::updateOrCreate(
            ['tenant_id' => $tenantId, 'customer_id' => $customerId],
            [
                'uuid'                      => Str::uuid()->toString(),
                'total_orders'              => $totalOrders,
                'total_spent'               => $totalSpent,
                'average_ticket'            => $avgTicket,
                'purchase_frequency'        => $frequency,
                'last_purchase_at'          => $lastPurchaseAt,
                'days_since_last_purchase'  => $daysSinceLast,
                'computed_at'               => now(),
            ],
        );
    }

    public function consolidateProduct(string $productId, string $tenantId): ProductMetrics
    {
        // SaleItem → Variant → product_id path (SaleItem lacks direct product_id)
        $variantIds = Variant::query()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->pluck('uuid');

        $saleData = SaleItem::query()
            ->whereIn('product_variant_id', $variantIds)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.uuid')
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'completed')
            ->selectRaw('SUM(sale_items.quantity) as total_units, SUM(sale_items.total_cents) as total_revenue_cents, MAX(sales.completed_at) as last_sale_at')
            ->first();

        $unitsSold    = (int) ($saleData?->total_units ?? 0);
        $grossRevenue = $this->calculator->centsToDecimal((int) ($saleData?->total_revenue_cents ?? 0));
        $lastSaleAt   = $saleData?->last_sale_at ? Carbon::parse($saleData->last_sale_at) : null;

        // Return rate: units returned / units sold
        $unitsReturned = SaleReturnItem::query()
            ->whereIn('variant_id', $variantIds)
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.uuid')
            ->where('sale_returns.tenant_id', $tenantId)
            ->sum('sale_return_items.quantity_returned');

        $returnRate    = $this->calculator->returnRate($unitsSold, (int) $unitsReturned);
        $daysWithout   = $this->calculator->daysSinceLastPurchase($lastSaleAt);

        return ProductMetrics::updateOrCreate(
            ['tenant_id' => $tenantId, 'product_id' => $productId],
            [
                'uuid'             => Str::uuid()->toString(),
                'units_sold'       => $unitsSold,
                'gross_revenue'    => $grossRevenue,
                'return_rate'      => $returnRate,
                'stock_turnover'   => 0,   // recomputed periodically with stock data
                'last_sale_at'     => $lastSaleAt,
                'days_without_sale' => $daysWithout,
                'computed_at'      => now(),
            ],
        );
    }

    public function consolidateChannel(string $channelId, string $tenantId): ChannelMetrics
    {
        $data = DB::table('omnichannel_orders')
            ->where('tenant_id', $tenantId)
            ->where('channel_id', $channelId)
            ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(total_amount),0) as revenue')
            ->first();

        $ordersCount = (int) ($data?->orders_count ?? 0);
        $revenue     = (float) ($data?->revenue ?? 0);
        $avgTicket   = $this->calculator->averageTicket($ordersCount, $revenue);

        return ChannelMetrics::updateOrCreate(
            ['tenant_id' => $tenantId, 'channel_id' => $channelId],
            [
                'uuid'          => Str::uuid()->toString(),
                'orders_count'  => $ordersCount,
                'revenue'       => $revenue,
                'average_ticket' => $avgTicket,
                'computed_at'   => now(),
            ],
        );
    }

    public function consolidateStore(string $storeId, string $tenantId): StoreMetrics
    {
        $data = Sale::query()
            ->where('tenant_id', $tenantId)
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(total_cents),0) as revenue_cents, COUNT(DISTINCT customer_id) as customer_count')
            ->first();

        $salesCount    = (int) ($data?->sales_count ?? 0);
        $revenue       = $this->calculator->centsToDecimal((int) ($data?->revenue_cents ?? 0));
        $customerCount = (int) ($data?->customer_count ?? 0);

        return StoreMetrics::updateOrCreate(
            ['tenant_id' => $tenantId, 'store_id' => $storeId],
            [
                'uuid'            => Str::uuid()->toString(),
                'sales_count'     => $salesCount,
                'revenue'         => $revenue,
                'inventory_value' => 0,   // computed separately via stock snapshot
                'customer_count'  => $customerCount,
                'computed_at'     => now(),
            ],
        );
    }
}
