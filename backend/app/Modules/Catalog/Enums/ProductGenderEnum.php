<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum ProductGenderEnum: string
{
    case Male   = 'male';
    case Female = 'female';
    case Unisex = 'unisex';
    case Child  = 'child';
    case All    = 'all';

    public function label(): string
    {
        return match($this) {
            self::Male   => 'Masculino',
            self::Female => 'Feminino',
            self::Unisex => 'Unissex',
            self::Child  => 'Infantil',
            self::All    => 'Todos',
        };
    }
}
