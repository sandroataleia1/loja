<?php

declare(strict_types=1);

namespace App\Modules\Financial\Enums;

enum CollectionActionEnum: string
{
    case Whatsapp      = 'whatsapp';
    case Email         = 'email';
    case Sms           = 'sms';
    case BlockCustomer = 'block_customer';
    case NotifySeller  = 'notify_seller';

    public function label(): string
    {
        return match ($this) {
            self::Whatsapp      => 'Enviar mensagem WhatsApp',
            self::Email         => 'Enviar e-mail de cobrança',
            self::Sms           => 'Enviar SMS de cobrança',
            self::BlockCustomer => 'Bloquear cliente automaticamente',
            self::NotifySeller  => 'Notificar vendedor responsável',
        };
    }
}
