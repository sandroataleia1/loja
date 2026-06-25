<?php

declare(strict_types=1);

namespace App\Modules\Partners\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Partners\DTOs\PartnerDTO;
use App\Modules\Partners\Models\PartnerProfessional;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\ConflictException;

final readonly class CreatePartnerAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
    ) {}

    public function execute(PartnerDTO $dto): PartnerProfessional
    {
        $tenantId = TenantContext::getIdOrFail();

        if ($dto->document !== null) {
            $exists = PartnerProfessional::withoutGlobalScope(\App\Core\Tenancy\Scopes\TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('document', $dto->document)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                throw new ConflictException("Documento '{$dto->document}' já está cadastrado nesta empresa.");
            }
        }

        $code = $dto->code ?? $this->generateCode->execute($tenantId, SequenceEntityEnum::Partner);

        return PartnerProfessional::create([
            'tenant_id'                  => $tenantId,
            'code'                       => $code,
            'type'                       => $dto->type,
            'person_type'                => $dto->personType,
            'name'                       => $dto->name,
            'company_name'               => $dto->companyName,
            'document'                   => $dto->document,
            'email'                      => $dto->email,
            'phone'                      => $dto->phone,
            'whatsapp'                   => $dto->whatsapp,
            'notes'                      => $dto->notes,
            'is_active'                  => $dto->isActive ?? true,
            'referral_commission_percent' => $dto->referralCommissionPercent,
        ]);
    }
}
