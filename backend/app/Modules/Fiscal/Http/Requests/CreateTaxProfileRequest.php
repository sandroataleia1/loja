<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Requests;

use App\Modules\Fiscal\Enums\TaxRegimeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateTaxProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'regime'   => ['required', new Enum(TaxRegimeEnum::class)],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
