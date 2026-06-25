<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBrandRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo_url'    => ['nullable', 'url', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'is_active'                  => ['sometimes', 'boolean'],
            'manufacturer_cnpj'          => ['nullable', 'string', 'max:20'],
            'manufacturer_contact_name'  => ['nullable', 'string', 'max:200'],
            'manufacturer_contact_email' => ['nullable', 'email', 'max:254'],
            'manufacturer_contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
