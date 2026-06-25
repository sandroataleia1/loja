<?php

declare(strict_types=1);

namespace App\Core\Auth\Enums;

enum ApprovalStatusEnum: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired  = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pendente',
            self::Approved => 'Aprovado',
            self::Rejected => 'Rejeitado',
            self::Expired  => 'Expirado',
        };
    }

    /** Status terminais — não podem ser alterados por novas ações. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Pending  => false,
            self::Approved => true,
            self::Rejected => true,
            self::Expired  => true,
        };
    }
}
