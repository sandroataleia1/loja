<?php

declare(strict_types=1);

namespace App\Modules\Conditional\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreConditionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id'                  => ['required', 'uuid', 'exists:stores,uuid'],
            'customer_id'               => ['required', 'uuid', 'exists:customers,uuid'],
            'due_date'                  => ['required', 'date', 'after:today'],
            'notes'                     => ['nullable', 'string', 'max:2000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.variant_id'        => ['required', 'uuid', 'exists:catalog_variants,uuid'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.unit_price_cents'  => ['required', 'integer', 'min:0'],
        ];
    }
}
