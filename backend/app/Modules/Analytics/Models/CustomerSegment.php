<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupo nomeado de clientes para campanhas e automações.
 * is_system: segmentos criados pelo sistema não podem ser deletados pelo usuário.
 */
final class CustomerSegment extends BaseModel
{
    protected $table = 'customer_segments';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_system' => 'boolean',
        ]);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function members(): HasMany
    {
        return $this->hasMany(CustomerSegmentMember::class, 'customer_segment_id', 'uuid');
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeUserDefined(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    // ── Membership helpers ────────────────────────────────────────────────────

    public function addMember(string $customerId, string $tenantId): void
    {
        $this->members()->firstOrCreate(
            ['customer_id' => $customerId],
            ['tenant_id' => $tenantId],
        );
    }

    public function removeMember(string $customerId): void
    {
        $this->members()->where('customer_id', $customerId)->delete();
    }

    public function hasMember(string $customerId): bool
    {
        return $this->members()->where('customer_id', $customerId)->exists();
    }
}
