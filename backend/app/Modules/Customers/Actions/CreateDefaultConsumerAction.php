<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\Models\Customer;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Support\Facades\DB;

/**
 * Cria o consumidor padrão de um tenant.
 *
 * Existe exatamente um por tenant. Chamado via listener em TenantCreated.
 * CPF é null — consumidor final não tem CPF próprio.
 */
final readonly class CreateDefaultConsumerAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(string $tenantId): Customer
    {
        return DB::transaction(function () use ($tenantId): Customer {
            // Garante que não existe já um consumidor padrão (idempotência)
            $existing = Customer::withoutTenantScope()
                ->where('tenant_id', $tenantId)
                ->where('is_default_consumer', true)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            // Gera código salvando e restaurando TenantContext (pode ser chamado
            // durante provisionamento, quando não há contexto de request autenticado)
            $previousTenantId = TenantContext::getId();
            TenantContext::set($tenantId);

            try {
                $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::Customer);
            } finally {
                if ($previousTenantId !== null) {
                    TenantContext::set($previousTenantId);
                } else {
                    TenantContext::clear();
                }
            }

            return Customer::withoutTenantScope()->create([
                'tenant_id'           => $tenantId,
                'code'                => $code,
                'name'                => 'Consumidor Final',
                'cpf'                 => null,
                'is_default_consumer' => true,
                'is_active'           => true,
            ]);
        });
    }
}
