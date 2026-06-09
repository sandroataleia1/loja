<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateVariantsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'uuid', 'exists:catalog_products,uuid'],
            'grid_id'          => ['required', 'uuid', 'exists:catalog_grids,uuid'],
            'base_price_cents' => ['required', 'integer', 'min:0'],
            'sku_prefix'       => ['required', 'string', 'max:50'],
        ];
    }
}
