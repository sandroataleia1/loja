<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Projeção materializada — uma linha por produto (não por variante).
 * Sem SoftDeletes: métricas não são deletadas, são recalculadas.
 */
final class ProductMetrics extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table      = 'product_metrics';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'units_sold',
        'gross_revenue',
        'return_rate',
        'stock_turnover',
        'last_sale_at',
        'days_without_sale',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_revenue'   => 'decimal:2',
            'return_rate'     => 'decimal:4',
            'stock_turnover'  => 'decimal:4',
            'last_sale_at'    => 'datetime',
            'computed_at'     => 'datetime',
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
        ];
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function forProduct(string $productId): Builder
    {
        return static::query()->where('product_id', $productId);
    }

    public static function topSellers(int $limit = 10): Builder
    {
        return static::query()
            ->orderByDesc('units_sold')
            ->limit($limit);
    }

    /** Produtos sem venda nos últimos $days dias. */
    public static function stale(int $days = 30): Builder
    {
        return static::query()
            ->where('days_without_sale', '>=', $days)
            ->orderByDesc('days_without_sale');
    }

    /** Produtos com alta taxa de devolução. */
    public static function highReturn(float $threshold = 0.05): Builder
    {
        return static::query()
            ->where('return_rate', '>=', $threshold)
            ->orderByDesc('return_rate');
    }
}
