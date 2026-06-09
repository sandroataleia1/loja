<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Shared\Traits\BelongsToTenant;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Projeção materializada — uma linha por canal omnichannel.
 * Atualizada ao receber OrderPlaced/OrderPaid via listener.
 */
final class ChannelMetrics extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $table      = 'channel_metrics';
    protected $primaryKey = 'uuid';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'orders_count',
        'revenue',
        'average_ticket',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'revenue'        => 'decimal:2',
            'average_ticket' => 'decimal:2',
            'computed_at'    => 'datetime',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public static function forChannel(string $channelId): Builder
    {
        return static::query()->where('channel_id', $channelId);
    }

    public static function topByRevenue(int $limit = 10): Builder
    {
        return static::query()
            ->orderByDesc('revenue')
            ->limit($limit);
    }
}
