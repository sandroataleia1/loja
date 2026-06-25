<?php

declare(strict_types=1);

namespace App\Core\Auth\Enums;

enum ApprovalThresholdTypeEnum: string
{
    case Always     = 'always';
    case Amount     = 'amount';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Always     => 'Sempre',
            self::Amount     => 'Valor acima de',
            self::Percentage => 'Percentual acima de',
        };
    }
}
