<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateStockCountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id'       => ['required', 'uuid', 'exists:stores,uuid'],
            'variant_ids'    => ['nullable', 'array'],
            'variant_ids.*'  => ['uuid', 'exists:catalog_variants,uuid'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }
}
