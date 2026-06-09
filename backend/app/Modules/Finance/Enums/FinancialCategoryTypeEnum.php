<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum FinancialCategoryTypeEnum: string
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
}
