<?php

declare(strict_types=1);

namespace App\Core\Auth\Enums;

/**
 * Tipos de operação que requerem aprovação de um gestor.
 *
 * Fundação para workflow de aprovação — o workflow completo não está implementado.
 * Actions que requerem aprovação podem checar este enum para decidir
 * se devem bloquear ou criar um ApprovalRequest pendente no futuro.
 */
enum ApprovalRequiredEnum: string
{
    case Discount     = 'discount';
    case Cancellation = 'cancellation';
    case Reversal     = 'reversal';
    case LargeRefund  = 'large_refund';

    public function label(): string
    {
        return match ($this) {
            self::Discount     => 'Aprovação de desconto',
            self::Cancellation => 'Aprovação de cancelamento',
            self::Reversal     => 'Aprovação de estorno',
            self::LargeRefund  => 'Aprovação de reembolso alto',
        };
    }
}
