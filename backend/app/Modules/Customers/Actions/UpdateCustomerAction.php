<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Customers\DTOs\UpdateCustomerDTO;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAddress;
use App\Modules\Customers\Models\CustomerContact;
use App\Shared\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCustomerAction
{
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

        return DB::transaction(function () use ($customer, $dto): Customer {
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

            // Replace addresses when provided
            if ($dto->addresses !== null) {
                $customer->addresses()->delete();

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
            }

            // Replace contacts when provided
            if ($dto->contacts !== null) {
                $customer->contacts()->delete();

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
            }

            // Sync tags when provided
            if ($dto->tags !== null) {
                $customer->tags()->sync($dto->tags);
            }

            return $customer->refresh();
        });
    }
}
