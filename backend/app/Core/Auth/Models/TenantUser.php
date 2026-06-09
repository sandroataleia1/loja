<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Modules\Inventory\Models\Store;
use App\Shared\Models\BaseModel;
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
 * is_active=true  → usuário ativo no tenant
 * is_active=false → acesso revogado (RevokeRoleAction)
 */
final class TenantUser extends BaseModel
{
    protected $table = 'tenant_users';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'role_id',
        'is_active',
        'joined_at',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
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
            'tenant_user_id', // FK em tenant_user_stores
            'uuid',           // FK em stores
            'uuid',           // PK em tenant_users
            'store_id',       // FK para stores em tenant_user_stores
        );
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    /** Sem entradas em tenant_user_stores = acesso irrestrito a todas as lojas do tenant. */
    public function hasUnrestrictedStoreAccess(): bool
    {
        return $this->storeAccesses()->doesntExist();
    }
}
