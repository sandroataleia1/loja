<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum ProductStatusEnum: string
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
    /** Produto sazonal: visível apenas no período configurado (starts_at/ends_at). */
    case Seasonal = 'seasonal';

    public function label(): string
    {
        return match($this) {
            self::Draft    => 'Rascunho',
            self::Active   => 'Ativo',
            self::Inactive => 'Inativo',
            self::Archived => 'Arquivado',
            self::Seasonal => 'Sazonal',
        };
    }

    public function isPublishable(): bool
    {
        return match($this) {
            self::Draft, self::Inactive, self::Seasonal => true,
            default                                     => false,
        };
    }

    public function isVisible(): bool
    {
        return $this === self::Active || $this === self::Seasonal;
    }

    public function canBeArchived(): bool
    {
        return $this !== self::Archived;
    }
}
