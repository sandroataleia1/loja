<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum FinancialEntryTypeEnum: string
{
    case Income  = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income  => 'Receita',
            self::Expense => 'Despesa',
        };
    }

    /** Sinal para atualização do saldo da conta financeira. */
    public function balanceSign(): int
    {
        return match ($this) {
            self::Income  => 1,
            self::Expense => -1,
        };
    }
}
