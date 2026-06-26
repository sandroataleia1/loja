<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Requests;

use App\Core\Rules\ValidCpf;
use App\Core\Tenancy\Rules\ValidCnpj;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        $isCompany = $this->input('person_type') === 'COMPANY';

        return [
            'person_type'             => ['sometimes', 'string', 'in:INDIVIDUAL,COMPANY'],
            'name'                    => ['sometimes', 'required', 'string', 'max:200'],
            'trade_name'              => ['nullable', 'string', 'max:150'],
            'document'                => ['nullable', 'string', 'max:20', $isCompany ? new ValidCnpj() : new ValidCpf()],
            'rg'                      => ['nullable', 'string', 'max:20'],
            'ie'                      => ['nullable', 'string', 'max:30'],
            'im'                      => ['nullable', 'string', 'max:20'],
            'situation'               => ['nullable', 'string', 'in:active,inactive,suspended,blocked'],
            'credit_limit'            => ['nullable', 'numeric', 'min:0'],
            'email'                   => ['nullable', 'email', 'max:254'],
            'birth_date'              => ['nullable', 'date', 'before:today'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'is_active'               => ['sometimes', 'boolean'],

            'addresses'               => ['nullable', 'array', 'max:10'],
            'addresses.*.address_type' => ['nullable', 'string', 'in:delivery,billing,commercial,headquarters'],
            'addresses.*.zipcode'     => ['required_with:addresses.*', 'string', 'max:10'],
            'addresses.*.street'      => ['required_with:addresses.*', 'string', 'max:200'],
            'addresses.*.number'      => ['required_with:addresses.*', 'string', 'max:20'],
            'addresses.*.complement'  => ['nullable', 'string', 'max:100'],
            'addresses.*.district'    => ['required_with:addresses.*', 'string', 'max:100'],
            'addresses.*.city'        => ['required_with:addresses.*', 'string', 'max:100'],
            'addresses.*.state'       => ['required_with:addresses.*', 'string', 'size:2'],
            'addresses.*.country'     => ['nullable', 'string', 'size:2'],
            'addresses.*.is_default'  => ['nullable', 'boolean'],

            'contacts'                => ['nullable', 'array', 'max:20'],
            'contacts.*.type'         => ['required_with:contacts.*', 'string', 'in:PHONE,WHATSAPP,EMAIL,INSTAGRAM,OTHER'],
            'contacts.*.value'        => ['required_with:contacts.*', 'string', 'max:200'],
            'contacts.*.label'        => ['nullable', 'string', 'max:100'],
            'contacts.*.is_primary'   => ['nullable', 'boolean'],

            'tags'                    => ['nullable', 'array'],
            'tags.*'                  => ['uuid', 'exists:customer_tags,uuid'],

            'civil_status'     => ['nullable', 'string', 'in:SINGLE,MARRIED,DIVORCED,WIDOWED,STABLE_UNION,OTHER'],
            'spouse_name'      => ['nullable', 'string', 'max:200'],
            'spouse_document'  => ['nullable', 'string', 'max:20'],
            'spouse_phone'     => ['nullable', 'string', 'max:20'],
            'spouse_employer'  => ['nullable', 'string', 'max:200'],
            'spouse_income'    => ['nullable', 'numeric', 'min:0'],
            'guarantor_name'     => ['nullable', 'string', 'max:200'],
            'guarantor_document' => ['nullable', 'string', 'max:20'],
            'guarantor_phone'    => ['nullable', 'string', 'max:20'],
            'guarantor_address'  => ['nullable', 'string', 'max:500'],
            'guarantor_income'   => ['nullable', 'numeric', 'min:0'],
            'spouse_profession'    => ['nullable', 'string', 'max:200'],
            'spouse_birth_date'    => ['nullable', 'date', 'before:today'],
            'spouse_gender'        => ['nullable', 'string', 'in:M,F,O'],
            'guarantor_profession' => ['nullable', 'string', 'max:200'],
            'guarantor_birth_date' => ['nullable', 'date', 'before:today'],
            'guarantor_gender'     => ['nullable', 'string', 'in:M,F,O'],
            'purchase_references'  => ['nullable', 'array', 'max:10'],
            'purchase_references.*.person_type'   => ['required_with:purchase_references.*', 'string', 'in:CUSTOMER,SPOUSE,GUARANTOR'],
            'purchase_references.*.company_name'  => ['required_with:purchase_references.*', 'string', 'max:200'],
            'purchase_references.*.phone'         => ['nullable', 'string', 'max:20'],
            'purchase_references.*.monthly_limit' => ['nullable', 'numeric', 'min:0'],
            'commercial_references'                  => ['nullable', 'array', 'max:10'],
            'commercial_references.*.company_name'   => ['required_with:commercial_references.*', 'string', 'max:200'],
            'commercial_references.*.contact_person' => ['nullable', 'string', 'max:200'],
            'commercial_references.*.phone'          => ['nullable', 'string', 'max:20'],
            'commercial_references.*.notes'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
