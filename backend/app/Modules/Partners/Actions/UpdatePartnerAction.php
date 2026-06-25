<?php

declare(strict_types=1);

namespace App\Modules\Partners\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Partners\DTOs\PartnerDTO;
use App\Modules\Partners\Models\PartnerProfessional;
use App\Shared\Exceptions\ConflictException;

final readonly class UpdatePartnerAction
{
    public function execute(PartnerProfessional $partner, PartnerDTO $dto): PartnerProfessional
    {
        $tenantId = TenantContext::getIdOrFail();

        if ($dto->document !== null) {
            $exists = PartnerProfessional::withoutGlobalScope(\App\Core\Tenancy\Scopes\TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('document', $dto->document)
                ->whereNull('deleted_at')
                ->where('uuid', '!=', $partner->uuid)
                ->exists();

            if ($exists) {
                throw new ConflictException("Documento '{$dto->document}' já está cadastrado nesta empresa.");
            }
        }

        $fields = array_filter([
            'type'                        => $dto->type,
            'name'                        => $dto->name,
            'person_type'                 => $dto->personType,
            'company_name'                => $dto->companyName,
            'document'                    => $dto->document,
            'email'                       => $dto->email,
            'phone'                       => $dto->phone,
            'whatsapp'                    => $dto->whatsapp,
            'notes'                       => $dto->notes,
            'is_active'                   => $dto->isActive,
            'referral_commission_percent' => $dto->referralCommissionPercent,
        ], fn ($v) => $v !== null);

        if (! empty($fields)) {
            $partner->update($fields);
        }

        return $partner->refresh();
    }
}
