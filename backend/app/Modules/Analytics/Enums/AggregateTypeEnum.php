<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Enums;

enum AggregateTypeEnum: string
{
    case Product  = 'product';
    case Variant  = 'variant';
    case Customer = 'customer';
    case Sale     = 'sale';
    case Order    = 'order';
    case Channel  = 'channel';
    case Store    = 'store';
    case Campaign = 'campaign';
    case Cart     = 'cart';

    public function label(): string
    {
        return match ($this) {
            self::Product  => 'Produto',
            self::Variant  => 'Variante',
            self::Customer => 'Cliente',
            self::Sale     => 'Venda PDV',
            self::Order    => 'Pedido',
            self::Channel  => 'Canal',
            self::Store    => 'Loja',
            self::Campaign => 'Campanha',
            self::Cart     => 'Carrinho',
        };
    }
}
