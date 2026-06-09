<?php

declare(strict_types=1);

namespace App\Modules\Sales\Enums;

enum SaleReturnStatusEnum: string
{
    case Pending    = 'pending';
    case Approved   = 'approved';
    case Processed  = 'processed';
    case Refunded   = 'refunded';
    case Rejected   = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendente',
            self::Approved  => 'Aprovada',
            self::Processed => 'Processada',
            self::Refunded  => 'Reembolsada',
            self::Rejected  => 'Rejeitada',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Refunded, self::Rejected], true);
    }

    public function canApprove(): bool   { return $this === self::Pending; }
    public function canProcess(): bool   { return $this === self::Approved; }
    public function canRefund(): bool    { return $this === self::Processed; }
    public function canReject(): bool    { return $this === self::Pending || $this === self::Approved; }
}
