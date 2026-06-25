<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum CustomerStatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Blocked  = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Ativo',
            self::Inactive => 'Inativo',
            self::Blocked  => 'Bloqueado',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
