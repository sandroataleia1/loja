<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Enums;

enum ConditionalStatusEnum: string
{
    case Open               = 'open';
    case PartiallyReturned  = 'partially_returned';
    case Returned           = 'returned';
    case PartiallyConverted = 'partially_converted';
    case Converted          = 'converted';
    case Overdue            = 'overdue';
    case Cancelled          = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open               => 'Aberto',
            self::PartiallyReturned  => 'Parcialmente Devolvido',
            self::Returned           => 'Devolvido',
            self::PartiallyConverted => 'Parcialmente Convertido',
            self::Converted          => 'Convertido',
            self::Overdue            => 'Vencido',
            self::Cancelled          => 'Cancelado',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Returned, self::Converted, self::Cancelled]);
    }

    public function canReturn(): bool
    {
        return in_array($this, [self::Open, self::PartiallyReturned, self::PartiallyConverted, self::Overdue]);
    }

    public function canConvert(): bool
    {
        return in_array($this, [self::Open, self::PartiallyReturned, self::PartiallyConverted, self::Overdue]);
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Open, self::Overdue]);
    }
}
