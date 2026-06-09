<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'parent_id'   => ['nullable', 'uuid', 'exists:catalog_categories,uuid'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url'   => ['nullable', 'url', 'max:500'],
            'sort_order'  => ['integer', 'min:0'],
            'is_active'   => ['boolean'],
        ];
    }
}
