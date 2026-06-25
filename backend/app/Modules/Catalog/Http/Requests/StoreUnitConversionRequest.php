<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Enums\UnitOfMeasureEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreUnitConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_unit'  => ['required', 'string', new Enum(UnitOfMeasureEnum::class)],
            'to_unit'    => ['required', 'string', new Enum(UnitOfMeasureEnum::class), 'different:from_unit'],
            'factor'     => ['required', 'numeric', 'min:0.000001'],
            'product_id' => ['nullable', 'uuid', 'exists:catalog_products,uuid'],
            'variant_id' => ['nullable', 'uuid', 'exists:catalog_variants,uuid'],
            'notes'      => ['nullable', 'string', 'max:255'],
            'is_active'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_unit.different' => 'A unidade de origem deve ser diferente da unidade de destino.',
            'factor.min'          => 'O fator de conversão deve ser maior que zero.',
        ];
    }
}
