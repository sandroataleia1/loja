<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Enums;

/**
 * Métricas com captura diária via DailyMetricSnapshot.
 *
 * Convenção de nomenclatura:
 * - Prefixo indica dimensão: tenant_, channel_, store_, product_, customer_
 * - Sem prefixo = tenant-level aggregate
 */
enum MetricNameEnum: string
{
    // ── Tenant-level ─────────────────────────────────────────────────────────
    case TotalRevenue       = 'total_revenue';
    case TotalSales         = 'total_sales';
    case TotalOrders        = 'total_orders';
    case AverageTicket      = 'average_ticket';
    case NewCustomers       = 'new_customers';
    case ActiveCustomers    = 'active_customers';      // purchase in last 30 days
    case DormantCustomers   = 'dormant_customers';     // no purchase in 90+ days
    case TotalReturnRate    = 'total_return_rate';
    case TotalInventoryValue = 'total_inventory_value';

    // ── Channel-level (metadata.channel_id) ──────────────────────────────────
    case ChannelRevenue     = 'channel_revenue';
    case ChannelOrders      = 'channel_orders';
    case ChannelAvgTicket   = 'channel_avg_ticket';

    // ── Store-level (metadata.store_id) ──────────────────────────────────────
    case StoreRevenue       = 'store_revenue';
    case StoreSales         = 'store_sales';
    case StoreCustomers     = 'store_customers';

    // ── Campaign-level (metadata.campaign_id) ────────────────────────────────
    case CampaignConversions = 'campaign_conversions';
    case CampaignRevenue    = 'campaign_revenue';
    case CampaignRoas       = 'campaign_roas';

    public function label(): string
    {
        return match ($this) {
            self::TotalRevenue       => 'Receita total',
            self::TotalSales         => 'Total de vendas',
            self::TotalOrders        => 'Total de pedidos',
            self::AverageTicket      => 'Ticket médio',
            self::NewCustomers       => 'Novos clientes',
            self::ActiveCustomers    => 'Clientes ativos',
            self::DormantCustomers   => 'Clientes inativos',
            self::TotalReturnRate    => 'Taxa de devolução',
            self::TotalInventoryValue => 'Valor do estoque',
            self::ChannelRevenue     => 'Receita por canal',
            self::ChannelOrders      => 'Pedidos por canal',
            self::ChannelAvgTicket   => 'Ticket médio por canal',
            self::StoreRevenue       => 'Receita por loja',
            self::StoreSales         => 'Vendas por loja',
            self::StoreCustomers     => 'Clientes por loja',
            self::CampaignConversions => 'Conversões de campanha',
            self::CampaignRevenue    => 'Receita de campanha',
            self::CampaignRoas       => 'ROAS de campanha',
        };
    }
}
