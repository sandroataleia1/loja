<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Models;

use App\Modules\Omnichannel\Enums\ChannelTypeEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Channel extends BaseModel
{
    protected $table = 'channels';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'code',
        'name',
        'type',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'type'      => ChannelTypeEnum::class,
            'is_active' => 'boolean',
            'metadata'  => 'array',
        ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function channelProducts(): HasMany
    {
        return $this->hasMany(ChannelProduct::class, 'channel_id', 'uuid');
    }

    public function channelPrices(): HasMany
    {
        return $this->hasMany(ChannelPrice::class, 'channel_id', 'uuid');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(ChannelCredential::class, 'channel_id', 'uuid');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'channel_id', 'uuid');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, ChannelTypeEnum $type): Builder
    {
        return $query->where('type', $type);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function requiresCredentials(): bool
    {
        return $this->type->requiresCredentials();
    }

    public function isAsyncChannel(): bool
    {
        return $this->type->isAsyncChannel();
    }
}
