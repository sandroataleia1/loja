<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Core\Auth\Enums\TenantUserStatusEnum;
use App\Modules\Inventory\Models\Store;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Membership de um usuário em um tenant, com um papel (Role).
 *
 * Um usuário pode pertencer a múltiplos tenants com roles distintas.
 * Um TenantUser sem registros em TenantUserStore tem acesso irrestrito a todas as lojas.
 * Se houver TenantUserStore entries, o usuário só acessa as lojas listadas (allowlist).
 *
 * Status transitions:
 *   active    → usuário ativo, is_active = true
 *   inactive  → acesso permanentemente revogado, is_active = false
 *   suspended → acesso temporariamente bloqueado, is_active = false
 */
final class TenantUser extends BaseModel
{
    protected $table = 'tenant_users';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role_id',
        'is_active',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
            'status'    => TenantUserStatusEnum::class,
        ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'uuid');
    }

    public function storeAccesses(): HasMany
    {
        return $this->hasMany(TenantUserStore::class, 'tenant_user_id', 'uuid');
    }

    public function stores(): HasManyThrough
    {
        return $this->hasManyThrough(
            Store::class,
            TenantUserStore::class,
            'tenant_user_id',
            'uuid',
            'uuid',
            'store_id',
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TenantUserStatusEnum::Active->value);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', TenantUserStatusEnum::Inactive->value);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', TenantUserStatusEnum::Suspended->value);
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    /** Sem entradas em tenant_user_stores = acesso irrestrito a todas as lojas do tenant. */
    public function hasUnrestrictedStoreAccess(): bool
    {
        return $this->storeAccesses()->doesntExist();
    }

    /**
     * Suspende o acesso temporariamente, mantendo o histórico de membership.
     * Diferente de revoke (inactive), suspended pode ser revertido para active.
     */
    public function suspend(): void
    {
        $this->update([
            'status'    => TenantUserStatusEnum::Suspended->value,
            'is_active' => false,
        ]);
    }

    /** Reativa um usuário suspenso. */
    public function reactivate(): void
    {
        $this->update([
            'status'    => TenantUserStatusEnum::Active->value,
            'is_active' => true,
        ]);
    }
}
