<?php

declare(strict_types=1);

namespace App\Modules\Orders\Enums;

enum OrderStatusEnum: string
{
    case Pending    = 'pending';
    case Confirmed  = 'confirmed';
    case InProgress = 'in_progress';
    case Ready      = 'ready';
    case Delivered  = 'delivered';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pendente',
            self::Confirmed  => 'Confirmado',
            self::InProgress => 'Em andamento',
            self::Ready      => 'Pronto para retirada',
            self::Delivered  => 'Entregue',
            self::Completed  => 'Concluído',
            self::Cancelled  => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'yellow',
            self::Confirmed  => 'blue',
            self::InProgress => 'orange',
            self::Ready      => 'purple',
            self::Delivered  => 'teal',
            self::Completed  => 'green',
            self::Cancelled  => 'red',
        };
    }

    public function isActive(): bool
    {
        return !in_array($this, [self::Completed, self::Cancelled], strict: true);
    }

    /** Transições de status permitidas. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending    => [self::Confirmed, self::Cancelled],
            self::Confirmed  => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Ready, self::Cancelled],
            self::Ready      => [self::Delivered, self::Cancelled],
            self::Delivered  => [self::Completed],
            self::Completed  => [],
            self::Cancelled  => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }
}
