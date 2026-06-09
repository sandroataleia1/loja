<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Models;

use App\Modules\Omnichannel\Enums\OrderStatusEnum;
use App\Shared\Exceptions\BusinessException;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido omnichannel — entidade pré-fulfillment.
 *
 * NÃO é Sale. Sale é a transação de caixa gerada na execução física do pedido.
 * Fluxo: Order → payment → fulfillment → [Sale criada no PDV]
 */
final class Order extends BaseModel
{
    protected $table = 'omnichannel_orders';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'channel_id',
        'customer_id',
        'order_number',
        'status',
        'total_amount',
        'metadata',
        'placed_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'       => OrderStatusEnum::class,
            'total_amount' => 'decimal:2',
            'metadata'     => 'array',
            'placed_at'    => 'datetime',
        ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'uuid');
    }

    // ── State transitions ─────────────────────────────────────────────────────

    public function transitionTo(OrderStatusEnum $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new BusinessException(
                "Cannot transition order from {$this->status->value} to {$next->value}."
            );
        }

        $this->update(['status' => $next]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForChannel(Builder $query, string $channelId): Builder
    {
        return $query->where('channel_id', $channelId);
    }

    public function scopeWithStatus(Builder $query, OrderStatusEnum $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatusEnum::Pending);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generateOrderNumber(): string
    {
        return 'OC-' . now()->format('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }
}
