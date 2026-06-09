<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Enums\MovementTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $adjustableTypes = [
            MovementTypeEnum::Purchase->value,
            MovementTypeEnum::Return->value,
            MovementTypeEnum::Loss->value,
            MovementTypeEnum::Adjustment->value,
        ];

        return [
            'store_id'       => ['required', 'uuid', 'exists:stores,uuid'],
            'variant_id'     => ['required', 'uuid', 'exists:catalog_variants,uuid'],
            'quantity'       => ['required', 'integer', 'not_in:0'],
            'type'           => ['required', Rule::in($adjustableTypes)],
            'notes'          => ['nullable', 'string', 'max:500'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id'   => ['nullable', 'uuid'],
            'metadata'       => ['nullable', 'array'],
        ];
    }
}
