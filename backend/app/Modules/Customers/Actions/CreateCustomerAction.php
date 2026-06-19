<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

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
                'code'        => $code,
                'person_type' => $dto->personType->value,
                'name'        => $dto->name,
                'trade_name'  => $dto->tradeName,
                'document'    => $dto->document,
                'email'       => $dto->email,
                'birth_date'  => $dto->birthDate,
                'notes'       => $dto->notes,
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

            return $customer;
        });
    }
}
