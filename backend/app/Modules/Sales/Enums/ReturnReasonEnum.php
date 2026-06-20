<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum ReturnReasonEnum: string
{
    case Defective        = 'defective';
    case WrongItem        = 'wrong_item';
    case DoesNotFit       = 'does_not_fit';
    case CustomerChanged  = 'customer_changed_mind';
    case DamagedInTransit = 'damaged_in_transit';
    case QualityIssue     = 'quality_issue';
    case Other            = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Defective        => 'Produto com defeito',
            self::WrongItem        => 'Item errado enviado',
            self::DoesNotFit       => 'Produto inadequado / medida incorreta',
            self::CustomerChanged  => 'Desistência do cliente',
            self::DamagedInTransit => 'Avariado no transporte',
            self::QualityIssue     => 'Problema de qualidade',
            self::Other            => 'Outro motivo',
        };
    }

    public function isProductFault(): bool
    {
        return in_array($this, [self::Defective, self::WrongItem, self::QualityIssue, self::DamagedInTransit], true);
    }
}
