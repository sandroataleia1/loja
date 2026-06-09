<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum PaymentMethodEnum: string
{
    case Cash        = 'cash';
    case Pix         = 'pix';
    case CreditCard  = 'credit_card';
    case DebitCard   = 'debit_card';
    case StoreCredit = 'store_credit';
    case Voucher     = 'voucher';

    public function label(): string
    {
        return match ($this) {
            self::Cash        => 'Dinheiro',
            self::Pix         => 'PIX',
            self::CreditCard  => 'Cartão de crédito',
            self::DebitCard   => 'Cartão de débito',
            self::StoreCredit => 'Crédito da loja',
            self::Voucher     => 'Vale',
        };
    }

    /** Métodos com liquidação instantânea (marcados como PAID imediatamente). */
    public function isInstant(): bool
    {
        return match ($this) {
            self::Cash, self::Pix, self::StoreCredit, self::Voucher => true,
            self::CreditCard, self::DebitCard                       => false,
        };
    }
}
