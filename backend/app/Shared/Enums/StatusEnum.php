<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum StatusEnum: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Pending  = 'pending';
    case Archived = 'archived';

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Ativo',
            self::Inactive => 'Inativo',
            self::Pending  => 'Pendente',
            self::Archived => 'Arquivado',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
