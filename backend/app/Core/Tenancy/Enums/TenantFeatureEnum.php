<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Enums;

/**
 * Feature flags por tenant para habilitar módulos opcionais.
 *
 * Verificar via: TenantFeature::isEnabled($tenantId, TenantFeatureEnum::FiscalEnabled)
 */
enum TenantFeatureEnum: string
{
    // ── Módulos existentes ────────────────────────────────────────────────────
    case FiscalEnabled           = 'fiscal_enabled';
    case FinancialEnabled        = 'financial_enabled';
    case MultiStoreEnabled       = 'multi_store_enabled';
    case OfflineFirstEnabled     = 'offline_first_enabled';
    case ConditionalOrderEnabled = 'conditional_order_enabled';
    case SocialCommerceEnabled   = 'social_commerce_enabled';
    case AiCampaignsEnabled      = 'ai_campaigns_enabled';
    case OmnichannelEnabled      = 'omnichannel_enabled';

    // ── Módulos adicionados no Módulo 03 ─────────────────────────────────────
    case PurchasingEnabled       = 'purchasing_enabled';
    case LogisticsEnabled        = 'logistics_enabled';
    case CommissionsEnabled      = 'commissions_enabled';
    case CrmEnabled              = 'crm_enabled';
    case BiEnabled               = 'bi_enabled';
    case NfeEnabled              = 'nfe_enabled';
    case CustomPriceTableEnabled = 'custom_price_table_enabled';

    public function label(): string
    {
        return match ($this) {
            self::FiscalEnabled           => 'Módulo fiscal',
            self::FinancialEnabled        => 'Módulo financeiro',
            self::MultiStoreEnabled       => 'Multi-loja',
            self::OfflineFirstEnabled     => 'Modo offline (PDV)',
            self::ConditionalOrderEnabled => 'Pedidos condicionais',
            self::SocialCommerceEnabled   => 'Social commerce',
            self::AiCampaignsEnabled      => 'Campanhas com IA',
            self::OmnichannelEnabled      => 'Omnichannel',
            self::PurchasingEnabled       => 'Módulo de Compras',
            self::LogisticsEnabled        => 'Módulo de Logística',
            self::CommissionsEnabled      => 'Módulo de Comissões',
            self::CrmEnabled              => 'Módulo CRM',
            self::BiEnabled               => 'BI / Analytics',
            self::NfeEnabled              => 'NF-e (Nota Fiscal Eletrônica)',
            self::CustomPriceTableEnabled => 'Tabela de Preços personalizada',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FiscalEnabled           => 'Emissão de NFC-e e NF-e',
            self::FinancialEnabled        => 'Controle de contas a pagar e receber',
            self::MultiStoreEnabled       => 'Operação com múltiplas lojas',
            self::OfflineFirstEnabled     => 'PDV funcionando sem internet',
            self::ConditionalOrderEnabled => 'Pedidos condicionais para provadores',
            self::SocialCommerceEnabled   => 'Vendas via redes sociais',
            self::AiCampaignsEnabled      => 'Geração de campanhas por IA',
            self::OmnichannelEnabled      => 'Integração omnichannel',
            self::PurchasingEnabled       => 'Pedidos de compra e recebimento',
            self::LogisticsEnabled        => 'Controle de separação e expedição',
            self::CommissionsEnabled      => 'Cálculo e gestão de comissões',
            self::CrmEnabled              => 'CRM e relacionamento com clientes',
            self::BiEnabled               => 'Relatórios e dashboards analíticos',
            self::NfeEnabled              => 'Emissão de NF-e para pedidos B2B',
            self::CustomPriceTableEnabled => 'Tabelas de preços por cliente/grupo',
        };
    }
}
