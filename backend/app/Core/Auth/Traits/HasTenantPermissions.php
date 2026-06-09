<?php

declare(strict_types=1);

namespace App\Core\Auth\Traits;

use App\Core\Auth\Enums\PermissionEnum;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Services\PermissionCache;
use App\Core\Tenancy\Services\TenantContext;

/**
 * Adiciona verificação de permissões por tenant ao modelo User.
 *
 * Permissões são resolvidas via PermissionCache (Redis, TTL 15 min).
 * O acesso a lojas usa a allowlist em tenant_user_stores:
 *   ausência de registros = irrestrito
 *   presença de registros = apenas as lojas listadas
 *
 * Uso nas policies:
 *   $user->hasPermission(PermissionEnum::SalesDiscount)
 *   $user->canAccessStore($storeId)
 */
trait HasTenantPermissions
{
    public function hasPermission(string|PermissionEnum $permission): bool
    {
        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return in_array($slug, $this->resolvePermissions(), true);
    }

    public function hasAnyPermission(string|PermissionEnum ...$permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(string|PermissionEnum ...$permissions): bool
    {
        foreach ($permissions as $perm) {
            if (! $this->hasPermission($perm)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica acesso a uma loja no tenant atual.
     * null em allowed_stores = irrestrito; array = allowlist.
     */
    public function canAccessStore(string $storeId): bool
    {
        $tenantId = TenantContext::getId();
        if ($tenantId === null) {
            return false;
        }

        $allowed = app(PermissionCache::class)->getAllowedStores($this->uuid, $tenantId);

        return $allowed === null || in_array($storeId, $allowed, true);
    }

    /** Membership ativa do usuário no tenant atual. */
    public function tenantMembership(?string $tenantId = null): ?TenantUser
    {
        $tenantId ??= TenantContext::getId();
        if ($tenantId === null) {
            return null;
        }

        return TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $this->uuid)
            ->where('is_active', true)
            ->first();
    }

    /** Todas as memberships do usuário (multiempresa). */
    public function allTenantMemberships()
    {
        return TenantUser::where('user_id', $this->uuid)->with('role')->get();
    }

    private function resolvePermissions(): array
    {
        $tenantId = TenantContext::getId();
        if ($tenantId === null) {
            return [];
        }

        return app(PermissionCache::class)->getPermissions($this->uuid, $tenantId);
    }
}
