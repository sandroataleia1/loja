<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Auth\Events\RoleRevoked;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Services\PermissionCache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Revoga o acesso de um usuário a um tenant (is_active = false).
 * Não remove o registro para preservar histórico de auditoria.
 */
final class RevokeRoleAction
{
    public function __construct(private readonly PermissionCache $cache) {}

    public function execute(string $tenantId, string $userId, ?string $revokedBy = null): void
    {
        $tenantUser = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        if ($tenantUser === null) {
            throw new NotFoundHttpException("Usuário não possui membership ativa nesta empresa.");
        }

        DB::transaction(function () use ($tenantUser, $tenantId, $userId, $revokedBy): void {
            $tenantUser->update(['is_active' => false]);

            $this->cache->invalidateUser($userId, $tenantId);

            RoleRevoked::dispatch($tenantUser, $revokedBy);
        });
    }
}
