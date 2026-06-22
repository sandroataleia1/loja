<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Core\Auth\Models\User;
use App\Modules\Finance\Models\PaymentCondition;
use App\Modules\Finance\Models\PaymentMethod;
use App\Modules\Orders\Enums\QuoteStatusEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Quote extends BaseModel
{
    use SoftDeletes;

    protected $table = 'quotes';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'customer_id',
        'payment_method_id',
        'payment_condition_id',
        'seller_id',
        'number',
        'status',
        'validity_days',
        'valid_until',
        'discount_type',
        'discount_value',
        'discount_cents',
        'subtotal_cents',
        'total_cents',
        'notes',
        'internal_notes',
        'payment_terms',
        'converted_to_order_id',
        'converted_at',
        'sent_at',
        'viewed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'status'         => QuoteStatusEnum::class,
            'validity_days'  => 'integer',
            'valid_until'    => 'date',
            'discount_value' => 'decimal:2',
            'discount_cents' => 'integer',
            'subtotal_cents' => 'integer',
            'total_cents'    => 'integer',
            'converted_at'   => 'datetime',
            'sent_at'        => 'datetime',
            'viewed_at'      => 'datetime',
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quote_id', 'uuid')->orderBy('sort_order');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_to_order_id', 'uuid');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id', 'uuid');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'uuid');
    }

    public function paymentCondition(): BelongsTo
    {
        return $this->belongsTo(PaymentCondition::class, 'payment_condition_id', 'uuid');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }

    /** Recalcula subtotal e total a partir dos itens (chama após criar/atualizar itens). */
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
