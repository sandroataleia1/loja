<?php

declare(strict_types=1);

namespace App\Modules\Sync\Enums;

enum SyncOperationStatusEnum: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Synced     = 'synced';
    case Failed     = 'failed';
    case Conflict   = 'conflict';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pendente',
            self::Processing => 'Processando',
            self::Synced     => 'Sincronizado',
            self::Failed     => 'Falhou',
            self::Conflict   => 'Conflito',
        };
    }

    public function isTerminal(): bool
    {
        return match($this) {
            self::Synced, self::Failed, self::Conflict => true,
            default                                     => false,
        };
    }

    public function canRetry(): bool
    {
        return $this === self::Failed;
    }
}
