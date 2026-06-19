<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Finance\DTOs\CreateFinancialAccountDTO;
use App\Modules\Finance\Models\FinancialAccount;
use App\Shared\Exceptions\ConflictException;

final readonly class CreateFinancialAccountAction
{
    public function execute(CreateFinancialAccountDTO $dto): FinancialAccount
    {
        $tenantId = TenantContext::getIdOrFail();

        if (FinancialAccount::where('code', $dto->code)->exists()) {
            throw new ConflictException("Código '{$dto->code}' já está em uso nesta empresa.");
        }

        return FinancialAccount::create([
            'store_id'              => $dto->storeId,
            'code'                  => $dto->code,
            'name'                  => $dto->name,
            'type'                  => $dto->type,
            'is_active'             => $dto->isActive,
            'current_balance_cents' => $dto->openingBalanceCents,
        ]);
    }
}
