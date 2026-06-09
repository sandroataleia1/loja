<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'origin_store_id'      => ['required', 'uuid', 'exists:stores,uuid'],
            'destination_store_id' => ['required', 'uuid', 'exists:stores,uuid', 'different:origin_store_id'],
            'items'                => ['required', 'array', 'min:1'],
            'items.*.variant_id'   => ['required', 'uuid', 'exists:catalog_variants,uuid'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ];
    }
}
