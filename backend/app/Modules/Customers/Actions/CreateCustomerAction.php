<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\DTOs\CreateCustomerDTO;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAddress;
use App\Modules\Customers\Models\CustomerContact;
use App\Shared\Actions\GenerateInternalCodeAction;
use App\Shared\Enums\SequenceEntityEnum;
use App\Shared\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;

final readonly class CreateCustomerAction
{
    public function __construct(
        private GenerateInternalCodeAction $generateCode,
        private AuditLogger $audit,
    ) {}

    public function execute(CreateCustomerDTO $dto): Customer
    {
        $tenantId = TenantContext::getIdOrFail();

        // Validate document uniqueness within the tenant
        if ($dto->document !== null) {
            $exists = Customer::withoutGlobalScope(\App\Core\Tenancy\Scopes\TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('document', $dto->document)
                ->whereNotNull('document')
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                throw new ConflictException("Documento '{$dto->document}' já está cadastrado nesta empresa.");
            }
        }

        return DB::transaction(function () use ($dto, $tenantId): Customer {
            $code = $this->generateCode->execute($tenantId, SequenceEntityEnum::Customer);

            $customer = Customer::create([
                'code'               => $code,
                'person_type'        => $dto->personType->value,
                'name'               => $dto->name,
                'trade_name'         => $dto->tradeName,
                'document'           => $dto->document,
                'rg'                 => $dto->rg,
                'ie'                 => $dto->ie,
                'im'                 => $dto->im,
                'situation'          => $dto->situation ?? 'active',
                'credit_limit'       => $dto->creditLimit ?? 0,
                'email'              => $dto->email,
                'birth_date'         => $dto->birthDate,
                'notes'              => $dto->notes,
                'civil_status'       => $dto->civilStatus,
                'spouse_name'        => $dto->spouseName,
                'spouse_document'    => $dto->spouseDocument,
                'spouse_phone'       => $dto->spousePhone,
                'spouse_employer'    => $dto->spouseEmployer,
                'spouse_income'      => $dto->spouseIncome,
                'guarantor_name'        => $dto->guarantorName,
                'guarantor_document'    => $dto->guarantorDocument,
                'guarantor_phone'       => $dto->guarantorPhone,
                'guarantor_address'     => $dto->guarantorAddress,
                'guarantor_income'      => $dto->guarantorIncome,
                'spouse_profession'     => $dto->spouseProfession,
                'spouse_birth_date'     => $dto->spouseBirthDate,
                'spouse_gender'         => $dto->spouseGender,
                'guarantor_profession'  => $dto->guarantorProfession,
                'guarantor_birth_date'  => $dto->guarantorBirthDate,
                'guarantor_gender'      => $dto->guarantorGender,
            ]);

            // Create addresses
            if (! empty($dto->addresses)) {
                $hasDefault = collect($dto->addresses)->contains(fn ($a) => ! empty($a['is_default']));

                foreach ($dto->addresses as $index => $addressData) {
                    $isDefault = ! empty($addressData['is_default']) || (! $hasDefault && $index === 0);

                    CustomerAddress::create([
                        'customer_id' => $customer->uuid,
                        'zipcode'     => $addressData['zipcode'],
                        'street'      => $addressData['street'],
                        'number'      => $addressData['number'],
                        'complement'  => $addressData['complement'] ?? null,
                        'district'    => $addressData['district'],
                        'city'        => $addressData['city'],
                        'state'       => $addressData['state'],
                        'country'     => $addressData['country'] ?? 'BR',
                        'is_default'  => $isDefault,
                    ]);
                }
            }

            // Create contacts
            if (! empty($dto->contacts)) {
                foreach ($dto->contacts as $contactData) {
                    CustomerContact::create([
                        'customer_id' => $customer->uuid,
                        'type'        => $contactData['type'],
                        'value'       => $contactData['value'],
                        'label'       => $contactData['label'] ?? null,
                        'is_primary'  => $contactData['is_primary'] ?? false,
                    ]);
                }
            }

            // Sync tags
            if (! empty($dto->tags)) {
                $customer->tags()->sync($dto->tags);
            }

            // Sync commercial references
            if (! empty($dto->commercialReferences)) {
                $customer->commercialReferences()->delete();
                foreach ($dto->commercialReferences as $ref) {
                    $customer->commercialReferences()->create([
                        'company_name'   => $ref['company_name'],
                        'contact_person' => $ref['contact_person'] ?? null,
                        'phone'          => $ref['phone'] ?? null,
                        'notes'          => $ref['notes'] ?? null,
                    ]);
                }
            }

            // Sync purchase references
            if (! empty($dto->purchaseReferences)) {
                $customer->purchaseReferences()->delete();
                foreach ($dto->purchaseReferences as $ref) {
                    $customer->purchaseReferences()->create([
                        'person_type'   => $ref['person_type'],
                        'company_name'  => $ref['company_name'],
                        'phone'         => $ref['phone'] ?? null,
                        'monthly_limit' => isset($ref['monthly_limit']) ? (float) $ref['monthly_limit'] : null,
                    ]);
                }
            }

            $this->audit->record(new AuditLogDTO(
                entityType: AuditEntityTypeEnum::Customer,
                entityUuid: $customer->uuid,
                action:     AuditActionEnum::CustomerCreated,
                tenantId:   $tenantId,
                userId:     auth()->id(),
                newValues:  ['name' => $customer->name, 'document' => $customer->document],
                ip:         request()->ip(),
                userAgent:  request()->userAgent(),
            ));

            return $customer;
        });
    }
}
