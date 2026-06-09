<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum AttributeTypeEnum: string
{
    case Text    = 'text';
    case Color   = 'color';
    case Number  = 'number';
    case Boolean = 'boolean';

    public function label(): string
    {
        return match($this) {
            self::Text    => 'Texto',
            self::Color   => 'Cor',
            self::Number  => 'Número',
            self::Boolean => 'Sim/Não',
        };
    }
}
