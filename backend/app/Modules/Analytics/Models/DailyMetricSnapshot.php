<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Modules\Analytics\Enums\MetricNameEnum;
use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Série temporal de métricas — ponto de captura diário.
 * Append-only com upsert idempotente via unique(tenant_id, metric_name, metric_date).
 * Sem UPDATED_AT: linha é imutável após criada; upsert substitui o valor.
 *
 * Bridge para Data Warehouse: ETL consulta SELECT * ORDER BY metric_date.
 */
final class DailyMetricSnapshot extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'daily_metric_snapshots';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'metric_name',
        'metric_date',
        'metric_value',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metric_name'  => MetricNameEnum::class,
            'metric_date'  => 'date',
            'metric_value' => 'decimal:4',
            'metadata'     => 'array',
            'created_at'   => 'datetime',
        ];
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function forMetric(MetricNameEnum $metric, string $tenantId): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('metric_name', $metric)
            ->orderBy('metric_date');
    }

    public static function forPeriod(string $tenantId, \DateTimeInterface $from, \DateTimeInterface $to): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('metric_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->orderBy('metric_date');
    }

    /**
     * Upsert idempotente — garante uma linha por (tenant_id, metric_name, metric_date).
     */
    public static function record(
        string $tenantId,
        MetricNameEnum $metric,
        \DateTimeInterface $date,
        float $value,
        ?array $metadata = null,
    ): static {
        $instance = static::firstOrNew([
            'tenant_id'   => $tenantId,
            'metric_name' => $metric->value,
            'metric_date' => $date->format('Y-m-d'),
        ]);

        $instance->metric_value = $value;
        $instance->metadata     = $metadata;

        if (!$instance->exists) {
            $instance->uuid = (string) \Illuminate\Support\Str::uuid();
        }

        $instance->save();

        return $instance;
    }
}
