<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum PersonTypeEnum: string
{
    case Individual = 'INDIVIDUAL';
    case Company    = 'COMPANY';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Pessoa Física',
            self::Company    => 'Pessoa Jurídica',
        };
    }

    public function documentLabel(): string
    {
        return match ($this) {
            self::Individual => 'CPF',
            self::Company    => 'CNPJ',
        };
    }
}
