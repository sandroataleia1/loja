<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum ReconciliationStatusEnum: string
{
    case Pending    = 'pending';
    case Reconciled = 'reconciled';
    case Divergent  = 'divergent';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pendente',
            self::Reconciled => 'Conciliado',
            self::Divergent  => 'Divergente',
        };
    }
}
