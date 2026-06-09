<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Inventory\Events\StoreCreated;
use App\Modules\Inventory\Models\Store;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;

/**
 * Cria a loja matriz padrão de um tenant.
 * Chamada dentro da transaction de registro — não dispara evento aqui,
 * o chamador é responsável por disparar StoreCreated após commit.
 */
final readonly class CreateDefaultStoreAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(string $tenantId): Store
    {
        $previousTenantId = TenantContext::getId();
        TenantContext::set($tenantId);

        try {
            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::Store);
        } finally {
            if ($previousTenantId !== null) {
                TenantContext::set($previousTenantId);
            } else {
                TenantContext::clear();
            }
        }

        $store = Store::withoutTenantScope()->create([
            'tenant_id' => $tenantId,
            'code'      => $code,
            'name'      => 'Matriz',
            'type'      => 'headquarters',
            'is_active' => true,
        ]);

        StoreCreated::dispatch($store);

        return $store;
    }
}
