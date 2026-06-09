<?php

declare(strict_types=1);

namespace App\Modules\Purchasing\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Purchasing\Models\Supplier;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use Illuminate\Support\Facades\DB;

final readonly class CreateSupplierAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(array $data): Supplier
    {
        $tenantId = TenantContext::getIdOrFail();

        return DB::transaction(function () use ($data, $tenantId): Supplier {
            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::Supplier);

            return Supplier::create([
                'tenant_id'   => $tenantId,
                'code'        => $code,
                'person_type' => $data['person_type'] ?? 'COMPANY',
                'name'        => $data['name'],
                'trade_name'  => $data['trade_name'] ?? null,
                'document'    => $data['document'] ?? null,
                'email'       => $data['email'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'is_active'   => $data['is_active'] ?? true,
                'notes'       => $data['notes'] ?? null,
            ]);
        });
    }
}
