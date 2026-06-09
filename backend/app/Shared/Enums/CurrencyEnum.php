<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum CurrencyEnum: string
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';

    public function symbol(): string
    {
        return match($this) {
            self::BRL => 'R$',
            self::USD => '$',
            self::EUR => '€',
        };
    }
}
