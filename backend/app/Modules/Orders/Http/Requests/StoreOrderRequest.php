<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'store_id'               => ['nullable', 'uuid', 'exists:stores,uuid'],
            'customer_id'            => ['nullable', 'uuid', 'exists:customers,uuid'],
            'quote_id'               => ['nullable', 'uuid', 'exists:quotes,uuid'],
            'discount_type'          => ['in:fixed,percent'],
            'discount_value'         => ['numeric', 'min:0'],
            'notes'                  => ['nullable', 'string', 'max:2000'],
            'internal_notes'         => ['nullable', 'string', 'max:2000'],
            'payment_terms'          => ['nullable', 'string', 'max:200'],
            'expected_at'            => ['nullable', 'date'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['nullable', 'uuid', 'exists:catalog_variants,uuid'],
            'items.*.name_snapshot'      => ['required', 'string', 'max:255'],
            'items.*.sku_snapshot'       => ['nullable', 'string', 'max:100'],
            'items.*.unit_of_measure'    => ['string', 'max:10'],
            'items.*.quantity'           => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price_cents'   => ['required', 'integer', 'min:0'],
            'items.*.discount_cents'     => ['integer', 'min:0'],
            'items.*.sort_order'         => ['integer', 'min:0'],
            'items.*.notes'              => ['nullable', 'string', 'max:500'],
        ];
    }
}
