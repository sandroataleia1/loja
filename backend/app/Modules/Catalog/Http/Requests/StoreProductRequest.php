<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Enums\ProductGenderEnum;
use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Enums\ProductVisibilityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:200'],
            'type'              => ['sometimes', Rule::enum(ProductTypeEnum::class)],
            'gender'            => ['sometimes', Rule::enum(ProductGenderEnum::class)],
            'status'            => ['sometimes', Rule::enum(ProductStatusEnum::class)],
            'visibility'        => ['sometimes', Rule::enum(ProductVisibilityEnum::class)],
            'base_price'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cost_price'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'brand_id'          => ['nullable', 'uuid', 'exists:catalog_brands,uuid'],
            'category_uuids'    => ['nullable', 'array'],
            'category_uuids.*'  => ['uuid', 'exists:catalog_categories,uuid'],
            'collection_id'     => ['nullable', 'uuid', 'exists:catalog_collections,uuid'],
            'grid_id'           => ['nullable', 'uuid', 'exists:catalog_grids,uuid'],
            'description'           => ['nullable', 'string'],
            'short_description'     => ['nullable', 'string', 'max:500'],
            'marketing_description' => ['nullable', 'string'],
            'internal_notes'        => ['nullable', 'string'],
            'is_featured'           => ['boolean'],
            'is_digital'            => ['boolean'],
            'is_publishable'        => ['boolean'],
            'season'                => ['nullable', 'string', 'max:50'],
            'launch_date'           => ['nullable', 'date'],
            'seo'               => ['nullable', 'array'],
            'seo.title'         => ['nullable', 'string', 'max:70'],
            'seo.description'   => ['nullable', 'string', 'max:160'],
            'seo.keywords'      => ['nullable', 'array'],
            'seo.keywords.*'    => ['string', 'max:50'],
        ];
    }
}
