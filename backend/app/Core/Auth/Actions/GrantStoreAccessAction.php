<?php

declare(strict_types=1);

namespace App\Core\Auth\Actions;

use App\Core\Auth\DTOs\GrantStoreAccessDTO;
use App\Core\Auth\Events\StoreAccessGranted;
use App\Core\Auth\Models\TenantUser;
use App\Core\Auth\Models\TenantUserStore;
use App\Core\Auth\Services\PermissionCache;
use Illuminate\Support\Facades\DB;

/**
 * Adiciona uma loja à allowlist de um TenantUser.
 * Se já existir, é idempotente.
 */
final class GrantStoreAccessAction
{
    public function __construct(private readonly PermissionCache $cache) {}

    public function execute(GrantStoreAccessDTO $dto, string $tenantId): TenantUserStore
    {
        $tenantUser = TenantUser::where('uuid', $dto->tenantUserId)->firstOrFail();

        return DB::transaction(function () use ($dto, $tenantUser, $tenantId): TenantUserStore {
            $storeAccess = TenantUserStore::firstOrCreate([
                'tenant_user_id' => $dto->tenantUserId,
                'store_id'       => $dto->storeId,
            ]);

            $this->cache->invalidateUser($tenantUser->user_id, $tenantId);

            StoreAccessGranted::dispatch($tenantUser, $dto->storeId);

            return $storeAccess;
        });
    }
}
