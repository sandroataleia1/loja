<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Auth\DTOs\AssignRoleDTO;
use App\Core\Auth\Events\RoleAssigned;
use App\Core\Auth\Models\Role;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Services\PermissionCache;
use Illuminate\Support\Facades\DB;

/**
 * Atribui (ou substitui) um role para um usuário em um tenant.
 *
 * Se o usuário já era membro do tenant, o role é substituído.
 * Invalida o cache de permissões do usuário imediatamente.
 */
final class AssignRoleAction
{
    public function __construct(private readonly PermissionCache $cache) {}

    public function execute(AssignRoleDTO $dto): TenantUser
    {
        $role = Role::where('uuid', $dto->roleId)
            ->where('tenant_id', $dto->tenantId)
            ->where('is_active', true)
            ->firstOrFail();

        return DB::transaction(function () use ($dto, $role): TenantUser {
            $tenantUser = TenantUser::updateOrCreate(
                ['tenant_id' => $dto->tenantId, 'user_id' => $dto->userId],
                [
                    'role_id'   => $dto->roleId,
                    'is_active' => true,
                    'joined_at' => now(),
                ],
            );

            $this->cache->invalidateUser($dto->userId, $dto->tenantId);

            RoleAssigned::dispatch($tenantUser, $role, $dto->assignedBy);

            return $tenantUser;
        });
    }
}
