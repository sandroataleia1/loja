<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum RoleEnum: string
{
    case Admin    = 'admin';
    case Manager  = 'manager';
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::Admin    => 'Administrador',
            self::Manager  => 'Gerente',
            self::Operator => 'Operador',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function canManage(): bool
    {
        return $this === self::Admin || $this === self::Manager;
    }
}
