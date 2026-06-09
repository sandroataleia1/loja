<?php

declare(strict_types=1);

namespace App\Core\Audit\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class DomainEventLog extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'domain_event_logs';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'correlation_id',
        'event_name',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'occurred_at' => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    public static function forEvent(string $eventName, ?string $tenantId = null): Builder
    {
        return static::query()
            ->where('event_name', $eventName)
            ->when($tenantId, fn (Builder $q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('occurred_at');
    }

    public static function forTenant(string $tenantId): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at');
    }
}
