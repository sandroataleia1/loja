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
    case FiscalEnabled           = 'fiscal_enabled';
    case FinancialEnabled        = 'financial_enabled';
    case MultiStoreEnabled       = 'multi_store_enabled';
    case OfflineFirstEnabled     = 'offline_first_enabled';
    case ConditionalOrderEnabled = 'conditional_order_enabled';
    case SocialCommerceEnabled   = 'social_commerce_enabled';
    case AiCampaignsEnabled      = 'ai_campaigns_enabled';
    case OmnichannelEnabled      = 'omnichannel_enabled';

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
        };
    }
}
