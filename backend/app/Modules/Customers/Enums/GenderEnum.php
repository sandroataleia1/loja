<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum GenderEnum: string
{
    case Male   = 'male';
    case Female = 'female';
    case Other  = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Male   => 'Masculino',
            self::Female => 'Feminino',
            self::Other  => 'Outro',
        };
    }
}
