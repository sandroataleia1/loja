<?php

declare(strict_types=1);

namespace App\Core\Auth\Services;

use App\Core\Auth\DTOs\PermissionSetDTO;
use App\Core\Auth\Models\TenantUser;
use DateTimeImmutable;

/**
 * Serviço centralizado de autorização por tenant.
 *
 * Produz PermissionSetDTO serializable para:
 * - Resposta de login (UserResource inclui permission_set)
 * - Payload de sync pull (offline-first — PDV armazena em SQLite)
 */
final class TenantAuthorizationService
{
    public function __construct(private readonly PermissionCache $cache) {}

    /**
     * Constrói o PermissionSetDTO do usuário no tenant.
     * Retorna null se o usuário não tiver membership ativa.
     */
    public function buildPermissionSet(string $userId, string $tenantId): ?PermissionSetDTO
    {
        $permissions   = $this->cache->getPermissions($userId, $tenantId);
        $allowedStores = $this->cache->getAllowedStores($userId, $tenantId);

        // Se permissões vazias e stores = [], pode não haver membership
        if (empty($permissions) && $allowedStores === []) {
            $exists = TenantUser::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->exists();

            if (! $exists) {
                return null;
            }
        }

        return new PermissionSetDTO(
            userId:          $userId,
            tenantId:        $tenantId,
            permissions:     $permissions,
            allowedStoreIds: $allowedStores,
            issuedAt:        new DateTimeImmutable(),
        );
    }

    public function invalidate(string $userId, string $tenantId): void
    {
        $this->cache->invalidateUser($userId, $tenantId);
    }
}
