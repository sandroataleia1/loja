<?php

declare(strict_types=1);

namespace App\Modules\Sync\Enums;

/**
 * Tipo de operação na operação de sincronização.
 *
 * Operações são append-only — nunca há UPDATE destrutivo.
 * Deletions usam tombstones (soft delete via operação DELETE).
 */
enum SyncOperationTypeEnum: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';

    public function label(): string
    {
        return match($this) {
            self::Create => 'Criação',
            self::Update => 'Atualização',
            self::Delete => 'Exclusão',
        };
    }
}
