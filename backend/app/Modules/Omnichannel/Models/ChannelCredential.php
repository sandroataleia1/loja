<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ChannelCredential extends BaseModel
{
    protected $table = 'channel_credentials';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'provider',
        'encrypted_credentials',
        'expires_at',
    ];

    protected $hidden = [
        'encrypted_credentials',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'encrypted_credentials' => 'encrypted',  // Crypt::encrypt/decrypt transparently
            'expires_at'            => 'datetime',
        ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'uuid');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /** Decode credentials payload (already decrypted by cast). */
    public function getCredentials(): array
    {
        $raw = $this->encrypted_credentials;

        return is_array($raw) ? $raw : json_decode((string) $raw, true) ?? [];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())
        );
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }
}
