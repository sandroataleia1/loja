<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum MovementTypeEnum: string
{
    case Sale              = 'sale';
    case Purchase          = 'purchase';
    case Transfer          = 'transfer';
    case Return            = 'return';
    case Loss              = 'loss';
    case Adjustment        = 'adjustment';
    case Inventory         = 'inventory';
    case Reserve           = 'reserve';
    case Release           = 'release';
    case In                = 'in';
    case Out               = 'out';
    case ConditionalOut    = 'conditional_out';
    case ConditionalReturn = 'conditional_return';

    public function label(): string
    {
        return match ($this) {
            self::Sale              => 'Venda',
            self::Purchase          => 'Compra',
            self::Transfer          => 'Transferência',
            self::Return            => 'Devolução',
            self::Loss              => 'Perda/Quebra',
            self::Adjustment        => 'Ajuste',
            self::Inventory         => 'Inventário',
            self::Reserve           => 'Reserva',
            self::Release           => 'Liberação',
            self::In                => 'Entrada',
            self::Out               => 'Saída',
            self::ConditionalOut    => 'Saída Condicional',
            self::ConditionalReturn => 'Retorno Condicional',
        };
    }

    /** Movimentos que reduzem o estoque disponível. */
    public function isNegative(): bool
    {
        return match ($this) {
            self::Sale, self::Loss, self::Transfer,
            self::Out, self::ConditionalOut        => true,
            default                                => false,
        };
    }

    /** Reserva/Liberação não alteram quantity, apenas reserved_quantity. */
    public function isReservation(): bool
    {
        return $this === self::Reserve || $this === self::Release;
    }
}
