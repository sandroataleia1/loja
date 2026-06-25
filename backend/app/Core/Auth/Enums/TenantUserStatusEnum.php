<?php

declare(strict_types=1);

namespace App\Core\Auth\Enums;

enum TenantUserStatusEnum: string
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

    /** Status que impedem o acesso ao tenant. */
    public function isBlocked(): bool
    {
        return match ($this) {
            self::Active    => false,
            self::Inactive  => true,
            self::Suspended => true,
        };
    }

    /** Status terminal — não pode ser revertido sem ação explícita do admin. */
    public function isTerminal(): bool
    {
        return $this === self::Inactive;
    }
}
