<?php

declare(strict_types=1);

namespace App\Core\Auth\Enums;

enum RoleSlugEnum: string
{
    case Owner         = 'owner';
    case Manager       = 'manager';
    case Salesperson   = 'salesperson';
    case Cashier       = 'cashier';
    case StockOperator = 'stock_operator';
    case Financial     = 'financial';

    public function label(): string
    {
        return match ($this) {
            self::Owner         => 'Proprietário',
            self::Manager       => 'Gerente',
            self::Salesperson   => 'Vendedor',
            self::Cashier       => 'Caixa',
            self::StockOperator => 'Operador de Estoque',
            self::Financial     => 'Financeiro',
        };
    }
}
