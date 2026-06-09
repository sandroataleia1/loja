<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum FinancialAccountTypeEnum: string
{
    case Cash          = 'cash';
    case Bank          = 'bank';
    case DigitalWallet = 'digital_wallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash          => 'Caixa',
            self::Bank          => 'Banco',
            self::DigitalWallet => 'Carteira Digital',
        };
    }
}
