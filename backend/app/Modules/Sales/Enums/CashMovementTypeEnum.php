<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum CashMovementTypeEnum: string
{
    case Opening    = 'opening';
    case Sale       = 'sale';
    case Withdrawal = 'withdrawal';
    case Supply     = 'supply';
    case Refund     = 'refund';
    case Adjustment = 'adjustment';
    case Closing    = 'closing';

    public function label(): string
    {
        return match ($this) {
            self::Opening    => 'Abertura',
            self::Sale       => 'Venda',
            self::Withdrawal => 'Sangria',
            self::Supply     => 'Suprimento',
            self::Refund     => 'Devolução',
            self::Adjustment => 'Ajuste',
            self::Closing    => 'Fechamento',
        };
    }

    /** Movimentações que aumentam o saldo físico */
    public function isCredit(): bool
    {
        return match ($this) {
            self::Opening, self::Sale, self::Supply => true,
            default                                 => false,
        };
    }

    /** Movimentações que diminuem o saldo físico */
    public function isDebit(): bool
    {
        return ! $this->isCredit();
    }
}
