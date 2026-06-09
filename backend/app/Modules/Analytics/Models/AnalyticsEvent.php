<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Modules\Analytics\Enums\AggregateTypeEnum;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Event Store analítico — append-only.
 *
 * Não usa BaseModel: sem BelongsToTenant (eventos não são deletados por tenant),
 * sem SoftDeletes (eventos são permanentes), sem Auditable.
 */
final class AnalyticsEvent extends Model
{
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'analytics_events';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'event_name',
        'aggregate_type',
        'aggregate_uuid',
        'payload',
        'metadata',
        'correlation_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_type' => AggregateTypeEnum::class,
            'payload'        => 'array',
            'metadata'       => 'array',
            'occurred_at'    => 'datetime',
            'created_at'     => 'datetime',
        ];
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function forAggregate(AggregateTypeEnum $type, string $uuid, string $tenantId): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('aggregate_type', $type)
            ->where('aggregate_uuid', $uuid)
            ->orderBy('occurred_at');
    }

    public static function forEvent(string $eventName, string $tenantId): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('event_name', $eventName)
            ->orderByDesc('occurred_at');
    }

    public static function forPeriod(string $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('occurred_at', [$from, $to])
            ->orderBy('occurred_at');
    }
}
