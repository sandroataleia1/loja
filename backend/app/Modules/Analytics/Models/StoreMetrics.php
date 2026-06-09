<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Projeção materializada — uma linha por loja física (PDV).
 * inventory_value calculado periodicamente via TakeDailySnapshotJob.
 */
final class StoreMetrics extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table      = 'store_metrics';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'sales_count',
        'revenue',
        'inventory_value',
        'customer_count',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'revenue'         => 'decimal:2',
            'inventory_value' => 'decimal:2',
            'computed_at'     => 'datetime',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function forStore(string $storeId): Builder
    {
        return static::query()->where('store_id', $storeId);
    }

    public static function topByRevenue(int $limit = 10): Builder
    {
        return static::query()
            ->orderByDesc('revenue')
            ->limit($limit);
    }
}
