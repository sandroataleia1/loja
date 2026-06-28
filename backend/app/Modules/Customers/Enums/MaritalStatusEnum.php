<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum MaritalStatusEnum: string
{
    case Single       = 'single';
    case Married      = 'married';
    case Divorced     = 'divorced';
    case Widowed      = 'widowed';
    case StableUnion  = 'stable_union';
    case Separated    = 'separated';

    public function label(): string
    {
        return match ($this) {
            self::Single      => 'Solteiro(a)',
            self::Married     => 'Casado(a)',
            self::Divorced    => 'Divorciado(a)',
            self::Widowed     => 'Viúvo(a)',
            self::StableUnion => 'União estável',
            self::Separated   => 'Separado(a)',
        };
    }

    public function hasSpouse(): bool
    {
        return in_array($this, [self::Married, self::StableUnion], true);
    }
}
