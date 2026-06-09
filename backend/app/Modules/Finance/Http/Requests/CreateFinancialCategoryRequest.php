<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinancialCategoryTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateFinancialCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'type'      => ['required', new Enum(FinancialCategoryTypeEnum::class)],
            'is_active' => ['boolean'],
        ];
    }
}
