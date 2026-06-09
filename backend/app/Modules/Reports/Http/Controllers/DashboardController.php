<?php

declare(strict_types=1);

namespace App\Modules\Reports\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use App\Modules\Sales\Enums\SaleStatusEnum;
use App\Shared\Traits\HasApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class DashboardController extends Controller
{
    use HasApiResponse;

    public function __invoke(): JsonResponse
    {
        $tenantId = TenantContext::getIdOrFail();
        $now      = Carbon::now();

        return $this->success([
            'kpis'          => $this->buildKpis($tenantId, $now),
            'salesChart'    => $this->buildSalesChart($tenantId, $now),
            'categoryChart' => $this->buildCategoryChart($tenantId, $now),
            'channelChart'  => $this->buildChannelChart($tenantId, $now),
            'alerts'        => [],
            'recentSales'   => $this->buildRecentSales($tenantId),
            'topProducts'   => $this->buildTopProducts($tenantId),
            'staleProducts' => $this->buildStaleProducts($tenantId),
        ]);
    }

    // ── KPIs ─────────────────────────────────────────────────────────────────

    private function buildKpis(string $tenantId, Carbon $now): array
    {
        $startCurrent = $now->copy()->startOfMonth();
        $startPrev    = $now->copy()->subMonth()->startOfMonth();
        $endPrev      = $now->copy()->subMonth()->endOfMonth();
        $last30       = $now->copy()->subDays(30);

        $currentRevenue = $this->revenueQuery($tenantId)
            ->where('completed_at', '>=', $startCurrent)
            ->sum('total_cents') / 100;

        $prevRevenue = $this->revenueQuery($tenantId)
            ->whereBetween('completed_at', [$startPrev, $endPrev])
            ->sum('total_cents') / 100;

        $currentOrders = $this->revenueQuery($tenantId)
            ->where('completed_at', '>=', $startCurrent)
            ->count();

        $prevOrders = $this->revenueQuery($tenantId)
            ->whereBetween('completed_at', [$startPrev, $endPrev])
            ->count();

        $currentTicket = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0.0;
        $prevTicket    = $prevOrders > 0    ? $prevRevenue / $prevOrders       : 0.0;

        $activeCustomers = $this->revenueQuery($tenantId)
            ->where('completed_at', '>=', $last30)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $prevActiveCustomers = $this->revenueQuery($tenantId)
            ->whereBetween('completed_at', [$startPrev, $endPrev])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $newCustomers = DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->where('is_default_consumer', false)
            ->where('created_at', '>=', $startCurrent)
            ->count();

        $prevNewCustomers = DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->where('is_default_consumer', false)
            ->whereBetween('created_at', [$startPrev, $endPrev])
            ->count();

        $inventoryValue = (float) DB::table('store_metrics')
            ->where('tenant_id', $tenantId)
            ->sum('inventory_value');

        return [
            $this->kpi('revenue',       'Receita do Mês',    $currentRevenue,      $prevRevenue,      'currency'),
            $this->kpi('orders',        'Pedidos',            $currentOrders,       $prevOrders,       'integer'),
            $this->kpi('ticket',        'Ticket Médio',       $currentTicket,       $prevTicket,       'currency'),
            $this->kpi('customers',     'Clientes Ativos',    $activeCustomers,     $prevActiveCustomers, 'integer'),
            $this->kpi('new_customers', 'Novos Clientes',     $newCustomers,        $prevNewCustomers, 'integer'),
            $this->kpi('inventory',     'Valor do Estoque',   $inventoryValue,      0.0,               'currency'),
        ];
    }

    private function revenueQuery(string $tenantId): \Illuminate\Database\Query\Builder
    {
        return DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->where('status', SaleStatusEnum::Completed->value);
    }

    private function kpi(string $id, string $label, float $current, float $prev, string $type): array
    {
        $change = $prev > 0
            ? round((($current - $prev) / $prev) * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        $trend = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral');

        $formatted = $type === 'currency'
            ? 'R$ ' . number_format($current, 2, ',', '.')
            : number_format((int) $current, 0, ',', '.');

        return [
            'id'           => $id,
            'label'        => $label,
            'value'        => $formatted,
            'rawValue'     => $current,
            'change'       => abs($change),
            'changePeriod' => 'vs. mês anterior',
            'trend'        => $trend,
            'icon'         => match ($id) {
                'revenue'       => 'DollarSign',
                'orders'        => 'ShoppingCart',
                'ticket'        => 'Receipt',
                'customers'     => 'Users',
                'new_customers' => 'UserPlus',
                'inventory'     => 'Package',
                default         => 'BarChart',
            },
            'prefix' => $type === 'currency' ? 'R$' : null,
        ];
    }

    // ── Charts ────────────────────────────────────────────────────────────────

    private function buildSalesChart(string $tenantId, Carbon $now): array
    {
        $from = $now->copy()->subDays(29)->startOfDay();

        $rows = DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->where('status', SaleStatusEnum::Completed->value)
            ->where('completed_at', '>=', $from)
            ->selectRaw("DATE(completed_at) as date, SUM(total_cents)/100.0 as revenue, COUNT(*) as orders")
            ->groupByRaw("DATE(completed_at)")
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $row = $rows->get($day);
            $result[] = [
                'date'    => $day,
                'revenue' => $row ? (float) $row->revenue : 0.0,
                'orders'  => $row ? (int) $row->orders   : 0,
            ];
        }

        return $result;
    }

    private function buildCategoryChart(string $tenantId, Carbon $now): array
    {
        $from = $now->copy()->subDays(29)->startOfDay();

        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.uuid')
            ->join('catalog_variants', 'sale_items.product_variant_id', '=', 'catalog_variants.uuid')
            ->join('catalog_products', 'catalog_variants.product_id', '=', 'catalog_products.uuid')
            ->join('catalog_product_categories', 'catalog_products.uuid', '=', 'catalog_product_categories.product_id')
            ->join('catalog_categories', 'catalog_product_categories.category_id', '=', 'catalog_categories.uuid')
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', SaleStatusEnum::Completed->value)
            ->where('sales.completed_at', '>=', $from)
            ->selectRaw('catalog_categories.name as category, SUM(sale_items.total_cents)/100.0 as revenue, COUNT(DISTINCT sales.uuid) as orders')
            ->groupBy('catalog_categories.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'revenue'  => (float) $r->revenue,
                'orders'   => (int) $r->orders,
            ])
            ->values()
            ->all();
    }

    private function buildChannelChart(string $tenantId, Carbon $now): array
    {
        $colors = [
            'pdv'         => '#6366f1',
            'ecommerce'   => '#22c55e',
            'whatsapp'    => '#10b981',
            'instagram'   => '#f59e0b',
            'marketplace' => '#ef4444',
        ];

        return DB::table('sales')
            ->where('tenant_id', $tenantId)
            ->where('status', SaleStatusEnum::Completed->value)
            ->where('completed_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->whereNotNull('sales_channel')
            ->selectRaw('sales_channel as channel, SUM(total_cents)/100.0 as value')
            ->groupBy('sales_channel')
            ->orderByDesc('value')
            ->get()
            ->map(function ($r) use ($colors) {
                $channel = $r->channel;
                return [
                    'channel' => $this->mapChannelLabel($channel),
                    'value'   => (float) $r->value,
                    'color'   => $colors[$channel] ?? '#94a3b8',
                ];
            })
            ->values()
            ->all();
    }

    // ── Widgets ───────────────────────────────────────────────────────────────

    private function buildRecentSales(string $tenantId): array
    {
        return DB::table('sales')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.uuid')
            ->where('sales.tenant_id', $tenantId)
            ->whereIn('sales.status', [
                SaleStatusEnum::Completed->value,
                SaleStatusEnum::Pending->value,
                SaleStatusEnum::Cancelled->value,
            ])
            ->select(
                'sales.uuid as id',
                'sales.total_cents',
                'sales.status',
                'sales.sales_channel',
                'sales.created_at',
                DB::raw("COALESCE(customers.name, 'Consumidor Final') as customer_name"),
                DB::raw("COALESCE(customers.email, '') as customer_email"),
            )
            ->orderByDesc('sales.created_at')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'customerName'  => $r->customer_name,
                'customerEmail' => $r->customer_email,
                'amount'        => $r->total_cents / 100,
                'status'        => $this->mapSaleStatus($r->status),
                'createdAt'     => $r->created_at,
                'channel'       => $this->mapChannelLabel($r->sales_channel),
            ])
            ->values()
            ->all();
    }

    private function buildTopProducts(string $tenantId): array
    {
        return DB::table('product_metrics')
            ->join('catalog_products', 'product_metrics.product_id', '=', 'catalog_products.uuid')
            ->where('product_metrics.tenant_id', $tenantId)
            ->where('product_metrics.units_sold', '>', 0)
            ->select(
                'product_metrics.product_id as id',
                'catalog_products.name',
                'catalog_products.code as sku',
                'product_metrics.gross_revenue as revenue',
                'product_metrics.units_sold as quantity',
                'product_metrics.return_rate',
            )
            ->orderByDesc('product_metrics.gross_revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id'       => $r->id,
                'name'     => $r->name,
                'sku'      => $r->sku,
                'revenue'  => (float) $r->revenue,
                'quantity' => (int) $r->quantity,
                'trend'    => (float) $r->return_rate > 0.10 ? 'down' : 'up',
            ])
            ->values()
            ->all();
    }

    private function buildStaleProducts(string $tenantId): array
    {
        return DB::table('product_metrics')
            ->join('catalog_products', 'product_metrics.product_id', '=', 'catalog_products.uuid')
            ->leftJoinSub(
                DB::table('inventory_balances')
                    ->join('catalog_variants', 'inventory_balances.variant_id', '=', 'catalog_variants.uuid')
                    ->selectRaw('catalog_variants.product_id, SUM(inventory_balances.quantity) as stock')
                    ->groupBy('catalog_variants.product_id'),
                'inv',
                'catalog_products.uuid',
                '=',
                'inv.product_id'
            )
            ->where('product_metrics.tenant_id', $tenantId)
            ->where('product_metrics.days_without_sale', '>=', 30)
            ->select(
                'product_metrics.product_id as id',
                'catalog_products.name',
                'catalog_products.code as sku',
                DB::raw('COALESCE(inv.stock, 0) as stock'),
                'product_metrics.last_sale_at as lastSoldAt',
                'product_metrics.days_without_sale as daysSinceSale',
            )
            ->orderByDesc('product_metrics.days_without_sale')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'name'          => $r->name,
                'sku'           => $r->sku,
                'stock'         => (int) $r->stock,
                'lastSoldAt'    => $r->lastSoldAt,
                'daysSinceSale' => (int) $r->daysSinceSale,
            ])
            ->values()
            ->all();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mapSaleStatus(string $status): string
    {
        return match ($status) {
            'completed'          => 'completed',
            'pending', 'draft'   => 'pending',
            default              => 'cancelled',
        };
    }

    private function mapChannelLabel(?string $channel): string
    {
        return match ($channel) {
            'pdv'         => 'PDV',
            'ecommerce'   => 'E-commerce',
            'whatsapp'    => 'WhatsApp',
            'instagram'   => 'Instagram',
            'marketplace' => 'Marketplace',
            default       => 'Outro',
        };
    }
}
