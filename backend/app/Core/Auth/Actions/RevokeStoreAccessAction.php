<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Auth\Events\StoreAccessRevoked;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\TenantUserStore;
use App\Core\Auth\Services\PermissionCache;
use Illuminate\Support\Facades\DB;

/**
 * Remove uma loja da allowlist de um TenantUser.
 * Se todas as lojas forem removidas, o acesso volta a ser irrestrito.
 */
final class RevokeStoreAccessAction
{
    public function __construct(private readonly PermissionCache $cache) {}

    public function execute(string $tenantUserId, string $storeId, string $tenantId): void
    {
        $tenantUser = TenantUser::where('uuid', $tenantUserId)->firstOrFail();

        DB::transaction(function () use ($tenantUserId, $storeId, $tenantUser, $tenantId): void {
            TenantUserStore::where('tenant_user_id', $tenantUserId)
                ->where('store_id', $storeId)
                ->delete();

            $this->cache->invalidateUser($tenantUser->user_id, $tenantId);

            StoreAccessRevoked::dispatch($tenantUser, $storeId);
        });
    }
}
