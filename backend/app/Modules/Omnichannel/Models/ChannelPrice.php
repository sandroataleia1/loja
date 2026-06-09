<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ChannelPrice extends BaseModel
{
    protected $table = 'channel_prices';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'product_id',
        'price',
        'promotional_price',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'price'             => 'decimal:2',
            'promotional_price' => 'decimal:2',
            'starts_at'         => 'datetime',
            'ends_at'           => 'datetime',
        ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'uuid');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function effectivePrice(): string
    {
        if ($this->isPromotionActive()) {
            return $this->promotional_price;
        }

        return $this->price;
    }

    public function isPromotionActive(): bool
    {
        if ($this->promotional_price === null) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeWithActivePromotion(Builder $query): Builder
    {
        $now = now();

        return $query
            ->whereNotNull('promotional_price')
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
