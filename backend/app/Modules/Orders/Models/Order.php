<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Modules\Orders\Enums\OrderStatusEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Order extends BaseModel
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'customer_id',
        'quote_id',
        'sale_id',
        'number',
        'status',
        'discount_type',
        'discount_value',
        'discount_cents',
        'subtotal_cents',
        'total_cents',
        'notes',
        'internal_notes',
        'payment_terms',
        'expected_at',
        'confirmed_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'         => OrderStatusEnum::class,
            'discount_value' => 'decimal:2',
            'discount_cents' => 'integer',
            'subtotal_cents' => 'integer',
            'total_cents'    => 'integer',
            'expected_at'    => 'date',
            'confirmed_at'   => 'datetime',
            'delivered_at'   => 'datetime',
            'completed_at'   => 'datetime',
            'cancelled_at'   => 'datetime',
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'uuid')->orderBy('sort_order');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id', 'uuid');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal_cents');
        $discount = $this->discount_type === 'percent'
            ? (int) round($subtotal * ($this->discount_value / 100))
            : $this->discount_cents;

        $this->update([
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents'    => max(0, $subtotal - $discount),
        ]);
    }
}
