<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Enums;

enum FiscalEnvironmentEnum: string
{
    case Homologation = 'homologation';
    case Production   = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Homologation => 'Homologação',
            self::Production   => 'Produção',
        };
    }

    public function isProduction(): bool
    {
        return $this === self::Production;
    }

    /** Código SEFAZ: 1=produção, 2=homologação. */
    public function tpAmb(): int
    {
        return $this === self::Production ? 1 : 2;
    }
}
