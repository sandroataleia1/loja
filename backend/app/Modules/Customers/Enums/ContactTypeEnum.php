<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

enum ContactTypeEnum: string
{
    case Phone     = 'PHONE';
    case Whatsapp  = 'WHATSAPP';
    case Email     = 'EMAIL';
    case Instagram = 'INSTAGRAM';
    case Other     = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::Phone     => 'Telefone',
            self::Whatsapp  => 'WhatsApp',
            self::Email     => 'E-mail',
            self::Instagram => 'Instagram',
            self::Other     => 'Outro',
        };
    }
}
