<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Enums\ProductStatusEnum;
use App\Modules\Catalog\Enums\ProductTypeEnum;
use App\Modules\Catalog\Enums\ProductVisibilityEnum;
use App\Modules\Catalog\Enums\UnitOfMeasureEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'              => ['sometimes', 'string', 'max:200'],
            'type'              => ['sometimes', Rule::enum(ProductTypeEnum::class)],
            'unit_of_measure'   => ['sometimes', 'nullable', Rule::enum(UnitOfMeasureEnum::class)],
            'status'            => ['sometimes', Rule::enum(ProductStatusEnum::class)],
            'visibility'        => ['sometimes', Rule::enum(ProductVisibilityEnum::class)],
            'base_price'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cost_price'        => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'brand_id'          => ['nullable', 'uuid', 'exists:catalog_brands,uuid'],
            'category_uuids'    => ['sometimes', 'nullable', 'array'],
            'category_uuids.*'  => ['uuid', 'exists:catalog_categories,uuid'],
            'collection_id'     => ['nullable', 'uuid', 'exists:catalog_collections,uuid'],
            'grid_id'           => ['nullable', 'uuid', 'exists:catalog_grids,uuid'],
            // Fiscal
            'ncm'              => ['sometimes', 'nullable', 'string', 'max:10'],
            'cest'             => ['sometimes', 'nullable', 'string', 'max:9'],
            'cfop_default'     => ['sometimes', 'nullable', 'string', 'max:5'],
            'origin_code'      => ['sometimes', 'nullable', 'integer', 'between:0,8'],
            'description'       => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'is_featured'       => ['sometimes', 'boolean'],
            'is_digital'        => ['sometimes', 'boolean'],
            'seo'               => ['sometimes', 'nullable', 'array'],
            'seo.title'         => ['nullable', 'string', 'max:70'],
            'seo.description'   => ['nullable', 'string', 'max:160'],
            'seo.keywords'      => ['nullable', 'array'],
            'seo.keywords.*'    => ['string', 'max:50'],
            'tags'              => ['sometimes', 'array'],
            'tags.*'            => ['string', 'max:100'],
        ];
    }
}
