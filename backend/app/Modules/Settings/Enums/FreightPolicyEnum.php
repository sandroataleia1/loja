<?php

declare(strict_types=1);

namespace App\Modules\Settings\Enums;

enum FreightPolicyEnum: string
{
    case Free            = 'free';
    case Fixed           = 'fixed';
    case Calculated      = 'calculated';
    case FreeAboveAmount = 'free_above_amount';

    public function label(): string
    {
        return match ($this) {
            self::Free            => 'Frete grátis',
            self::Fixed           => 'Frete fixo',
            self::Calculated      => 'Frete calculado',
            self::FreeAboveAmount => 'Frete grátis acima de valor',
        };
    }
}
