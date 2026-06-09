<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'person_type'             => ['sometimes', 'string', 'in:INDIVIDUAL,COMPANY'],
            'name'                    => ['sometimes', 'required', 'string', 'max:200'],
            'trade_name'              => ['nullable', 'string', 'max:150'],
            'document'                => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:254'],
            'birth_date'              => ['nullable', 'date', 'before:today'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'is_active'               => ['sometimes', 'boolean'],

            'addresses'               => ['nullable', 'array', 'max:10'],
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
        ];
    }
}
