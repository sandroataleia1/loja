<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum PlanEnum: string
{
    case Free       = 'free';
    case Starter    = 'starter';
    case Pro        = 'pro';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Free       => 'Grátis',
            self::Starter    => 'Starter',
            self::Pro        => 'Pro',
            self::Enterprise => 'Enterprise',
        };
    }

    public function isPaid(): bool
    {
        return $this !== self::Free;
    }
}
