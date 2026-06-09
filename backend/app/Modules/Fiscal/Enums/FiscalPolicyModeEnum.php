<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalPolicyModeEnum: string
{
    case Always      = 'always';
    case Never       = 'never';
    case Ask         = 'ask';
    case AboveAmount = 'above_amount';

    public function label(): string
    {
        return match ($this) {
            self::Always      => 'Sempre emitir',
            self::Never       => 'Nunca emitir',
            self::Ask         => 'Perguntar ao operador',
            self::AboveAmount => 'Acima de um valor',
        };
    }

    public function requiresAmount(): bool
    {
        return $this === self::AboveAmount;
    }
}
