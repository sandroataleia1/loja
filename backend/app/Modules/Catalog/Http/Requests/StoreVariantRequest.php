<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreVariantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'uuid', 'exists:catalog_products,uuid'],
            'sku'              => ['required', 'string', 'max:100'],
            'price_cents'      => ['required', 'integer', 'min:0'],
            'name'             => ['nullable', 'string', 'max:200'],
            'barcode'          => ['nullable', 'string', 'max:50'],
            'gtin'             => ['nullable', 'string', 'max:14'],
            'cost_cents'       => ['nullable', 'integer', 'min:0'],
            'compare_at_cents' => ['nullable', 'integer', 'min:0'],
            'weight_g'         => ['nullable', 'integer', 'min:0'],
            'dimensions'       => ['nullable', 'array'],
            'dimensions.width_cm'  => ['nullable', 'numeric', 'min:0'],
            'dimensions.height_cm' => ['nullable', 'numeric', 'min:0'],
            'dimensions.depth_cm'  => ['nullable', 'numeric', 'min:0'],
            'is_active'        => ['boolean'],
            'is_default'       => ['boolean'],
            'sort_order'       => ['integer', 'min:0'],
            'attribute_ids'    => ['nullable', 'array'],
            'attribute_ids.*'  => ['uuid', 'exists:catalog_attributes,uuid'],
        ];
    }
}
