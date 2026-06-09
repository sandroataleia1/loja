<?php

declare(strict_types=1);

namespace App\Core\Auth\Models;

use App\Shared\Traits\HasUuid;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Role de um tenant ou role de sistema (tenant_id = NULL).
 *
 * NÃO estende BaseModel: o BelongsToTenant global scope filtraria
 * roles de sistema (tenant_id = NULL) indevidamente.
 * Queries devem usar o scope forTenant() explicitamente.
 */
final class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    protected $table      = 'roles';
    protected $primaryKey = 'uuid';

    protected $fillable = ['tenant_id', 'name', 'slug', 'description', 'is_system', 'is_active', 'policy'];

    protected function casts(): array
    {
        return [
            'is_system'  => 'boolean',
            'is_active'  => 'boolean',
            'policy'     => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Roles editáveis do tenant (tenant_id = X).
     *
     * O bootstrap cria uma cópia por tenant de cada role de sistema — essas cópias
     * são os roles reais do tenant, editáveis (nome, permissões).
     * Os templates globais (tenant_id = NULL) são apenas blueprints de bootstrap e
     * NÃO devem aparecer na listagem do tenant (causariam duplicatas).
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query
            ->where('tenant_id', $tenantId)
            ->where('is_active', true);
    }

    /** Roles globais de sistema (blueprints de bootstrap, tenant_id = NULL). */
    public function scopeSystemOnly(Builder $query): Builder
    {
        return $query->whereNull('tenant_id');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class, 'role_permissions', 'role_id', 'permission_id', 'uuid', 'uuid',
        );
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class, 'role_id', 'uuid');
    }

    // ── Domain helpers ────────────────────────────────────────────────────────

    /**
     * Template global de sistema (tenant_id = NULL) — não pode ser editado nem deletado.
     * As cópias por tenant (is_system=false, tenant_id=X) são editáveis.
     */
    public function isSystemRole(): bool
    {
        return $this->tenant_id === null;
    }

    /** Percentual máximo de desconto permitido para usuários com este role. */
    public function discountLimitPercentage(): ?float
    {
        return isset($this->policy['discount_limit_percentage'])
            ? (float) $this->policy['discount_limit_percentage']
            : null;
    }
}
