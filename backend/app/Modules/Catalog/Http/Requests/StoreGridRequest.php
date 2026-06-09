<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreGridRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attribute_group_id' => ['required', 'uuid', 'exists:catalog_attribute_groups,uuid'],
            'name'               => ['required', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:500'],
            'attribute_ids'      => ['required', 'array', 'min:1'],
            'attribute_ids.*'    => ['uuid', 'exists:catalog_attributes,uuid'],
        ];
    }
}
