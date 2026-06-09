<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum StockCountStatusEnum: string
{
    case Draft      = 'draft';
    case InProgress = 'in_progress';
    case Committed  = 'committed';

    public function label(): string
    {
        return match ($this) {
            self::Draft      => 'Rascunho',
            self::InProgress => 'Em andamento',
            self::Committed  => 'Confirmado',
        };
    }

    public function canStart(): bool  { return $this === self::Draft; }
    public function canCommit(): bool { return $this === self::InProgress; }
}
