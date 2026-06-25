<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Core\Audit\DTOs\AuditLogDTO;
use App\Core\Audit\Enums\AuditActionEnum;
use App\Core\Audit\Enums\AuditEntityTypeEnum;
use App\Core\Audit\Services\AuditLogger;
use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\DTOs\UpdateCustomerDTO;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAddress;
use App\Modules\Customers\Models\CustomerContact;
use App\Shared\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCustomerAction
{
    public function __construct(
        private AuditLogger $audit,
    ) {}

    public function execute(Customer $customer, UpdateCustomerDTO $dto): Customer
    {
        $tenantId = TenantContext::getIdOrFail();

        // Validate document uniqueness excluding the current customer
        if ($dto->document !== null) {
            $exists = Customer::withoutGlobalScope(\App\Core\Tenancy\Scopes\TenantScope::class)
                ->where('tenant_id', $tenantId)
                ->where('document', $dto->document)
                ->whereNotNull('document')
                ->whereNull('deleted_at')
                ->where('uuid', '!=', $customer->uuid)
                ->exists();

            if ($exists) {
                throw new ConflictException("Documento '{$dto->document}' já está cadastrado nesta empresa.");
            }
        }

        return DB::transaction(function () use ($customer, $dto, $tenantId): Customer {
            $fields = [];

            if ($dto->personType !== null) {
                $fields['person_type'] = $dto->personType->value;
            }
            if ($dto->name !== null) {
                $fields['name'] = $dto->name;
            }
            if ($dto->tradeName !== null) {
                $fields['trade_name'] = $dto->tradeName;
            }
            if ($dto->document !== null) {
                $fields['document'] = $dto->document;
            }
            if ($dto->rg !== null) {
                $fields['rg'] = $dto->rg;
            }
            if ($dto->ie !== null) {
                $fields['ie'] = $dto->ie;
            }
            if ($dto->im !== null) {
                $fields['im'] = $dto->im;
            }
            if ($dto->situation !== null) {
                $fields['situation'] = $dto->situation;
            }
            if ($dto->creditLimit !== null) {
                $fields['credit_limit'] = $dto->creditLimit;
            }
            if ($dto->email !== null) {
                $fields['email'] = $dto->email;
            }
            if ($dto->birthDate !== null) {
                $fields['birth_date'] = $dto->birthDate;
            }
            if ($dto->notes !== null) {
                $fields['notes'] = $dto->notes;
            }
            if ($dto->isActive !== null) {
                $fields['is_active'] = $dto->isActive;
            }

            if (! empty($fields)) {
                $customer->update($fields);
            }

            // Upsert addresses: preserva UUIDs existentes, soft-deleta removidos
            if ($dto->addresses !== null) {
                $sentIds    = collect($dto->addresses)->pluck('id')->filter()->all();
                $hasDefault = collect($dto->addresses)->contains(fn ($a) => ! empty($a['is_default']));

                // Soft-delete os que não vieram no request
                $customer->addresses()
                    ->whereNotIn('uuid', $sentIds)
                    ->delete();

                foreach ($dto->addresses as $index => $addressData) {
                    $isDefault = ! empty($addressData['is_default']) || (! $hasDefault && $index === 0);

                    $payload = [
                        'customer_id'  => $customer->uuid,
                        'address_type' => $addressData['address_type'] ?? 'delivery',
                        'zipcode'      => $addressData['zipcode'],
                        'street'       => $addressData['street'],
                        'number'       => $addressData['number'],
                        'complement'   => $addressData['complement'] ?? null,
                        'district'     => $addressData['district'],
                        'city'         => $addressData['city'],
                        'state'        => $addressData['state'],
                        'country'      => $addressData['country'] ?? 'BR',
                        'is_default'   => $isDefault,
                    ];

                    if (! empty($addressData['id'])) {
                        CustomerAddress::withTrashed()
                            ->where('uuid', $addressData['id'])
                            ->update(array_merge($payload, ['deleted_at' => null]));
                    } else {
                        CustomerAddress::create($payload);
                    }
                }
            }

            // Upsert contacts: preserva UUIDs existentes, soft-deleta removidos
            if ($dto->contacts !== null) {
                $sentIds = collect($dto->contacts)->pluck('id')->filter()->all();

                $customer->contacts()
                    ->whereNotIn('uuid', $sentIds)
                    ->delete();

                foreach ($dto->contacts as $contactData) {
                    $payload = [
                        'customer_id' => $customer->uuid,
                        'type'        => $contactData['type'],
                        'value'       => $contactData['value'],
                        'label'       => $contactData['label'] ?? null,
                        'is_primary'  => $contactData['is_primary'] ?? false,
                    ];

                    if (! empty($contactData['id'])) {
                        CustomerContact::withTrashed()
                            ->where('uuid', $contactData['id'])
                            ->update(array_merge($payload, ['deleted_at' => null]));
                    } else {
                        CustomerContact::create($payload);
                    }
                }
            }

            // Sync tags when provided
            if ($dto->tags !== null) {
                $customer->tags()->sync($dto->tags);
            }

            $this->audit->record(new AuditLogDTO(
                entityType: AuditEntityTypeEnum::Customer,
                entityUuid: $customer->uuid,
                action:     AuditActionEnum::CustomerUpdated,
                tenantId:   $tenantId,
                userId:     auth()->id(),
                newValues:  array_filter($fields),
                ip:         request()->ip(),
                userAgent:  request()->userAgent(),
            ));

            return $customer->refresh();
        });
    }
}
