<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Auth\Services\PermissionCache;

/**
 * Invalida caches de permissão quando roles ou permissões mudam.
 *
 * Chamado por listeners registrados no AppServiceProvider:
 * - Ao sincronizar permissões de um role (RoleController::syncPermissions)
 * - Ao atribuir ou revogar roles (AssignRoleAction / RevokeRoleAction)
 */
final class SyncPermissionCacheAction
{
    public function __construct(private readonly PermissionCache $cache) {}

    /** Invalida cache de todos os usuários com um role no tenant (permissões do role mudaram). */
    public function invalidateByRole(string $tenantId, string $roleId): void
    {
        $this->cache->invalidateTenantRole($tenantId, $roleId);
    }

    /** Invalida cache de um usuário específico no tenant. */
    public function invalidateUser(string $userId, string $tenantId): void
    {
        $this->cache->invalidateUser($userId, $tenantId);
    }
}
