<?php

declare(strict_types=1);

namespace App\Core\Auth\Services;

use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\TenantUserStore;
use Illuminate\Support\Facades\Cache;

/**
 * Cache de permissões e acesso a lojas por usuário×tenant.
 *
 * Estratégia: chaves simples no Redis com TTL de 15 minutos.
 * Invalidação explícita ao mudar role, permissões do role, ou store access.
 *
 * Chaves:
 *   rbac.{tenantId}.{userId}.permissions → string[] de slugs
 *   rbac.{tenantId}.{userId}.stores      → null (irrestrito) | string[] de store UUIDs
 */
final class PermissionCache
{
    private const TTL_MINUTES = 15;

    /** @return string[] slugs das permissões do usuário no tenant */
    public function getPermissions(string $userId, string $tenantId): array
    {
        return Cache::remember(
            $this->permKey($userId, $tenantId),
            now()->addMinutes(self::TTL_MINUTES),
            fn () => $this->loadPermissions($userId, $tenantId),
        );
    }

    /**
     * @return null|string[]  null = irrestrito | [] = sem acesso | [uuid...] = allowlist
     */
    public function getAllowedStores(string $userId, string $tenantId): ?array
    {
        return Cache::remember(
            $this->storeKey($userId, $tenantId),
            now()->addMinutes(self::TTL_MINUTES),
            fn () => $this->loadAllowedStores($userId, $tenantId),
        );
    }

    public function invalidateUser(string $userId, string $tenantId): void
    {
        Cache::forget($this->permKey($userId, $tenantId));
        Cache::forget($this->storeKey($userId, $tenantId));
    }

    /** Invalida caches de todos os usuários com um determinado role (quando permissões do role mudam). */
    public function invalidateTenantRole(string $tenantId, string $roleId): void
    {
        TenantUser::where('tenant_id', $tenantId)
            ->where('role_id', $roleId)
            ->where('is_active', true)
            ->each(fn (TenantUser $tu) => $this->invalidateUser($tu->user_id, $tenantId));
    }

    // ── Loaders ───────────────────────────────────────────────────────────────

    private function loadPermissions(string $userId, string $tenantId): array
    {
        $tenantUser = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->with('role.permissions')
            ->first();

        if ($tenantUser === null || $tenantUser->role === null) {
            return [];
        }

        return $tenantUser->role->permissions->pluck('slug')->all();
    }

    private function loadAllowedStores(string $userId, string $tenantId): ?array
    {
        $tenantUser = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if ($tenantUser === null) {
            return []; // sem membership = sem acesso
        }

        $storeIds = TenantUserStore::where('tenant_user_id', $tenantUser->uuid)
            ->pluck('store_id')
            ->all();

        return empty($storeIds) ? null : $storeIds; // null = irrestrito
    }

    // ── Key builders ──────────────────────────────────────────────────────────

    private function permKey(string $userId, string $tenantId): string
    {
        return "rbac.{$tenantId}.{$userId}.permissions";
    }

    private function storeKey(string $userId, string $tenantId): string
    {
        return "rbac.{$tenantId}.{$userId}.stores";
    }
}
