<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum SpcStatusEnum: string
{
    case Clean      = 'clean';
    case Restricted = 'restricted';
    case Unknown    = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Clean      => 'Limpo',
            self::Restricted => 'Restrito',
            self::Unknown    => 'Não consultado',
        };
    }

    public function isRestricted(): bool
    {
        return $this === self::Restricted;
    }
}
