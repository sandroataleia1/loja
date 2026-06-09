<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Models;

use App\Modules\Omnichannel\Enums\ChannelSyncStatusEnum;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ChannelProduct extends BaseModel
{
    protected $table = 'channel_products';

    protected $fillable = [
        'tenant_id',
        'channel_id',
        'product_id',
        'is_published',
        'published_at',
        'external_reference',
        'sync_status',
        'metadata',
    ];

    /**
     * Default de modelo: produto começa não publicado. Sem isto, um registro
     * recém-criado sem `is_published` teria o atributo NULL em memória (o default
     * do banco só se reflete após refresh).
     */
    protected $attributes = [
        'is_published' => false,
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sync_status'  => ChannelSyncStatusEnum::class,
            'metadata'     => 'array',
        ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'uuid');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopePendingSync(Builder $query): Builder
    {
        return $query->where('sync_status', ChannelSyncStatusEnum::Pending);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('sync_status', ChannelSyncStatusEnum::Failed);
    }

    public function scopeNeedsSync(Builder $query): Builder
    {
        return $query->whereIn('sync_status', [
            ChannelSyncStatusEnum::Pending->value,
            ChannelSyncStatusEnum::Failed->value,
            ChannelSyncStatusEnum::Outdated->value,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function markSynced(string $externalReference): void
    {
        $this->update([
            'sync_status'        => ChannelSyncStatusEnum::Synced,
            'is_published'       => true,
            'published_at'       => $this->published_at ?? now(),
            'external_reference' => $externalReference,
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['sync_status' => ChannelSyncStatusEnum::Failed]);
    }

    public function markOutdated(): void
    {
        $this->update(['sync_status' => ChannelSyncStatusEnum::Outdated]);
    }
}
