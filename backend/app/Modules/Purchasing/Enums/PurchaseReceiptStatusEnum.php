<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Enums;

enum PurchaseReceiptStatusEnum: string
{
    case Pending   = 'pending';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendente',
            self::Completed => 'Concluído',
        };
    }
}
