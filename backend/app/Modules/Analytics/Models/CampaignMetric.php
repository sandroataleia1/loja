<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Série temporal de métricas por campanha — append-only.
 * Sem updated_at: apenas created_at (imutável).
 * campaign_id é UUID opaco — tabela de campanhas criada em sprint futura.
 *
 * Para dashboard: use last value por (campaign_id, metric_name, period_date).
 */
final class CampaignMetric extends Model
{
    use BelongsToTenant;
    use HasUuid;

    public const UPDATED_AT = null;

    protected $table      = 'campaign_metrics';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'metric_name',
        'metric_value',
        'period_date',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metric_value' => 'decimal:4',
            'period_date'  => 'date',
            'metadata'     => 'array',
            'created_at'   => 'datetime',
        ];
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function forCampaign(string $campaignId, string $tenantId): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('campaign_id', $campaignId)
            ->orderBy('period_date');
    }

    public static function forMetric(string $campaignId, string $metricName, string $tenantId): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('campaign_id', $campaignId)
            ->where('metric_name', $metricName)
            ->orderByDesc('period_date');
    }

    /** Última leitura de cada métrica para uma campanha (snapshot mais recente). */
    public static function latestForCampaign(string $campaignId, string $tenantId): Builder
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('campaign_id', $campaignId)
            ->orderByDesc('period_date')
            ->orderByDesc('created_at');
    }
}
