<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Enums;

enum FulfillmentTypeEnum: string
{
    case Pickup  = 'pickup';   // customer picks up at store
    case Delivery = 'delivery'; // last-mile delivery (motoboy, etc.)
    case Shipment = 'shipment'; // carrier shipment (Correios, transportadora)

    public function label(): string
    {
        return match ($this) {
            self::Pickup   => 'Retirada na loja',
            self::Delivery => 'Entrega local',
            self::Shipment => 'Envio por transportadora',
        };
    }

    /** Whether this fulfillment type requires a shipping address. */
    public function requiresAddress(): bool
    {
        return in_array($this, [self::Delivery, self::Shipment], true);
    }
}
