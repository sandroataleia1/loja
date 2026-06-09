<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Enums;

enum ChannelTypeEnum: string
{
    case Pdv         = 'pdv';
    case Instagram   = 'instagram';
    case Whatsapp    = 'whatsapp';
    case Ecommerce   = 'ecommerce';
    case Marketplace = 'marketplace';

    public function label(): string
    {
        return match ($this) {
            self::Pdv         => 'Ponto de Venda',
            self::Instagram   => 'Instagram',
            self::Whatsapp    => 'WhatsApp',
            self::Ecommerce   => 'E-commerce',
            self::Marketplace => 'Marketplace',
        };
    }

    /** Channels that require external API credentials. */
    public function requiresCredentials(): bool
    {
        return in_array($this, [self::Instagram, self::Whatsapp, self::Ecommerce, self::Marketplace], true);
    }

    /** Channels where publication is async (no immediate PDV sync). */
    public function isAsyncChannel(): bool
    {
        return $this !== self::Pdv;
    }
}
