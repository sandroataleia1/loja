<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Rules\NoCyclicCategoryRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        // Para updates, a categoria é passada como parâmetro de rota
        /** @var \App\Modules\Catalog\Models\Category|null $category */
        $category = $this->route('category');

        return [
            'name'        => ['required', 'string', 'max:100'],
            'parent_id'   => [
                'nullable',
                'uuid',
                'exists:catalog_categories,uuid',
                new NoCyclicCategoryRule(categoryUuid: $category?->uuid),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url'   => ['nullable', 'url', 'max:500'],
            'sort_order'  => ['integer', 'min:0'],
            'is_active'   => ['boolean'],
        ];
    }
}
