<?php

declare(strict_types=1);

namespace App\Core\Tenancy\Enums;

use App\Core\Features\FeatureEnum;

/**
 * Perfis de onboarding para novos tenants.
 *
 * Cada perfil ativa um conjunto pré-definido de feature flags e
 * configura TenantSettings com valores padrão adequados ao modelo de negócio.
 */
enum TenantProfileEnum: string
{
    case SmallStore  = 'small_store';  // Loja pequena — operação simples
    case Wholesale   = 'wholesale';    // Atacado puro — sem PDV, foco em pedidos
    case Distributor = 'distributor';  // Distribuidor — representantes + entregas
    case Custom      = 'custom';       // Personalizado — escolhe na mão

    public function label(): string
    {
        return match ($this) {
            self::SmallStore  => 'Loja Pequena',
            self::Wholesale   => 'Atacado',
            self::Distributor => 'Distribuidor',
            self::Custom      => 'Personalizado',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SmallStore  => 'Operação simples de balcão com crediário e NFC-e',
            self::Wholesale   => 'Pedidos B2B sem PDV, foco em NF-e e representantes',
            self::Distributor => 'Gestão de entregas, rotas e representantes externos',
            self::Custom      => 'Configure cada feature manualmente',
        };
    }

    /**
     * Features habilitadas por padrão para este perfil.
     *
     * @return FeatureEnum[]
     */
    public function defaultFeatures(): array
    {
        return match ($this) {
            self::SmallStore => [
                FeatureEnum::FiscalNfce,
                FeatureEnum::SalesInstallment,
                FeatureEnum::FinancialBoleto,
                FeatureEnum::CustomerGuarantor,
                FeatureEnum::CustomerSpouse,
                FeatureEnum::CustomerRegistrationCard,
            ],
            self::Wholesale => [
                FeatureEnum::FiscalNfe,
                FeatureEnum::SalesPartialOrder,
                FeatureEnum::SalesCommissionPartner,
                FeatureEnum::InventoryMultiWarehouse,
                FeatureEnum::OpsRepresentative,
                FeatureEnum::FinancialBoleto,
            ],
            self::Distributor => [
                FeatureEnum::FiscalNfe,
                FeatureEnum::FiscalNfce,
                FeatureEnum::OpsDeliveryRoutes,
                FeatureEnum::OpsDeliveryProof,
                FeatureEnum::OpsRepresentative,
                FeatureEnum::SalesCommissionPartner,
                FeatureEnum::InventoryMultiWarehouse,
                FeatureEnum::SalesWhatsapp,
                FeatureEnum::OpsWhatsappBot,
            ],
            self::Custom => [], // nenhuma feature pré-habilitada
        };
    }

    /**
     * Configurações padrão de TenantSettings para este perfil.
     *
     * @return array<string, array<string, mixed>> section => [key => value]
     */
    public function defaultSettings(): array
    {
        $base = [
            'commercial' => [
                'default_discount_limit'                  => 10.0,
                'requires_supervisor_for_discount_above'  => 15.0,
            ],
        ];

        return match ($this) {
            self::Wholesale => array_merge($base, [
                'commercial' => [
                    'default_discount_limit'                 => 20.0,
                    'requires_supervisor_for_discount_above' => 30.0,
                ],
            ]),
            self::Distributor => array_merge($base, [
                'logistics' => [
                    'delivery_routes_enabled' => true,
                    'proof_of_delivery'       => true,
                ],
            ]),
            default => $base,
        };
    }
}
