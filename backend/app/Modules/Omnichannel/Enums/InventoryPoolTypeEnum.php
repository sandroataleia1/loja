<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Enums;

enum InventoryPoolTypeEnum: string
{
    case Store       = 'store';       // physical store stock
    case Ecommerce   = 'ecommerce';   // reserved for online orders
    case Marketplace = 'marketplace'; // reserved for marketplace orders
    case Warehouse   = 'warehouse';   // distribution center

    public function label(): string
    {
        return match ($this) {
            self::Store       => 'Estoque loja',
            self::Ecommerce   => 'Estoque e-commerce',
            self::Marketplace => 'Estoque marketplace',
            self::Warehouse   => 'Centro de distribuição',
        };
    }

    /** Whether this pool is available for ship-from-store routing. */
    public function isShipFromStoreEligible(): bool
    {
        return $this === self::Store;
    }
}
