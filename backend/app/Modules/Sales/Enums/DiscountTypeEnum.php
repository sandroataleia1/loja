<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum DiscountTypeEnum: string
{
    case Percentage = 'percentage';
    case Fixed      = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Percentual',
            self::Fixed      => 'Valor fixo',
        };
    }
}
