<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Projeção materializada — uma linha por cliente.
 * Sem SoftDeletes: métricas não são deletadas, são recalculadas.
 * Sem Auditable: tabela computada, não entidade de domínio.
 */
final class CustomerMetrics extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table      = 'customer_metrics';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'total_orders',
        'total_spent',
        'average_ticket',
        'purchase_frequency',
        'last_purchase_at',
        'days_since_last_purchase',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_spent'              => 'decimal:2',
            'average_ticket'           => 'decimal:2',
            'purchase_frequency'       => 'decimal:2',
            'last_purchase_at'         => 'datetime',
            'computed_at'              => 'datetime',
            'created_at'               => 'datetime',
            'updated_at'               => 'datetime',
        ];
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function forCustomer(string $customerId): Builder
    {
        return static::query()->where('customer_id', $customerId);
    }

    /** Clientes sem compra nos últimos $days dias. */
    public static function dormant(int $days = 90): Builder
    {
        return static::query()
            ->where('days_since_last_purchase', '>=', $days)
            ->orderByDesc('days_since_last_purchase');
    }

    /** Clientes com compra nos últimos $days dias. */
    public static function active(int $days = 30): Builder
    {
        return static::query()
            ->where('last_purchase_at', '>=', now()->subDays($days))
            ->orderByDesc('last_purchase_at');
    }

    public static function topSpenders(int $limit = 10): Builder
    {
        return static::query()
            ->orderByDesc('total_spent')
            ->limit($limit);
    }
}
