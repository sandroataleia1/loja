<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Core\Audit\Observers\AuditObserver;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::observe(AuditObserver::class);
    }
}
