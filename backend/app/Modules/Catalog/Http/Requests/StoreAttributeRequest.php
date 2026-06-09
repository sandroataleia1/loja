<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAttributeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attribute_group_id' => ['required', 'uuid', 'exists:catalog_attribute_groups,uuid'],
            'value'              => ['required', 'string', 'max:100'],
            'label'              => ['required', 'string', 'max:100'],
            'color_hex'          => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order'         => ['integer', 'min:0'],
        ];
    }
}
