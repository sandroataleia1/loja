<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum SequenceEntityEnum: string
{
    case Product        = 'PRODUCT';
    case ProductVariant = 'PRODUCT_VARIANT';
    case Customer       = 'CUSTOMER';
    case Sale           = 'SALE';
    case Order          = 'ORDER';
    case Store          = 'STORE';
    case Channel        = 'CHANNEL';
    case Conditional    = 'CONDITIONAL';
    case Payment        = 'PAYMENT';
    case Transfer       = 'TRANSFER';
    case CashRegister   = 'CASH_REGISTER';
    case Brand          = 'brand';    // prefix 'MRC', padding 4 → MRC0001
    case Category       = 'category'; // prefix 'CAT', padding 4 → CAT0001
    case Supplier       = 'supplier';      // prefix 'FOR', padding 4 → FOR0001
    case PurchaseOrder  = 'purchase_order'; // prefix 'CPR', padding 4 → CPR0001

    public function prefix(): string
    {
        return match ($this) {
            self::Product        => 'PRO',
            self::ProductVariant => 'PRO', // prefix overridden in GenerateVariantCodeAction
            self::Customer       => 'CLI',
            self::Sale           => 'VEN',
            self::Order          => 'PED',
            self::Store          => 'LOJ',
            self::Channel        => 'CAN',
            self::Conditional    => 'CON',
            self::Payment        => 'PAY',
            self::Transfer       => 'TRF',
            self::CashRegister   => 'CAS',
            self::Brand          => 'MRC',
            self::Category       => 'CAT',
            self::Supplier       => 'FOR',
            self::PurchaseOrder  => 'CPR',
        };
    }

    public function padding(): int
    {
        return match ($this) {
            self::ProductVariant => 3,
            default              => 6,
        };
    }
}
