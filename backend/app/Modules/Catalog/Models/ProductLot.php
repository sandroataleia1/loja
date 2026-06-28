<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductLot extends BaseModel
{
    protected $table = 'product_lots';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'received_at',
        'initial_qty',
        'current_qty',
        'purchase_order_id',
        'supplier_id',
        'notes',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'manufacture_date' => 'date',
            'expiry_date'      => 'date',
            'received_at'      => 'date',
            'initial_qty'      => 'decimal:4',
            'current_qty'      => 'decimal:4',
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id', 'uuid');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function expiresInDays(): int
    {
        return $this->expiry_date ? (int) now()->diffInDays($this->expiry_date, false) : 9999;
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiresInDays() <= $days;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeExpiring(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('current_qty', '>', 0)
            ->where(fn (Builder $q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()));
    }
}
