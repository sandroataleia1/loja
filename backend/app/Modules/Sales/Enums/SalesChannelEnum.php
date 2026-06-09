<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

/**
 * Canal de origem da venda — suporte omnichannel desde a fundação.
 *
 * Vendas DEVEM registrar o canal de origem para:
 * - Analytics por canal
 * - Atribuição de campanhas futuras
 * - Motor de recomendações futuro
 * - Relatórios de performance por canal
 */
enum SalesChannelEnum: string
{
    case Pdv         = 'pdv';
    case Instagram   = 'instagram';
    case Whatsapp    = 'whatsapp';
    case Ecommerce   = 'ecommerce';
    case Marketplace = 'marketplace';

    public function label(): string
    {
        return match($this) {
            self::Pdv         => 'PDV',
            self::Instagram   => 'Instagram',
            self::Whatsapp    => 'WhatsApp',
            self::Ecommerce   => 'E-commerce',
            self::Marketplace => 'Marketplace',
        };
    }

    public function isDigital(): bool
    {
        return match($this) {
            self::Instagram, self::Whatsapp, self::Ecommerce, self::Marketplace => true,
            self::Pdv => false,
        };
    }
}
