<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Enums;

enum SupplierStatusEnum: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Ativo',
            self::Inactive  => 'Inativo',
            self::Suspended => 'Suspenso',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
