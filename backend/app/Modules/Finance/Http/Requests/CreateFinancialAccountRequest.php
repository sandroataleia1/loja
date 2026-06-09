<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinancialAccountTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateFinancialAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id'               => ['nullable', 'uuid', 'exists:stores,uuid'],
            'code'                   => ['required', 'string', 'max:30'],
            'name'                   => ['required', 'string', 'max:100'],
            'type'                   => ['required', new Enum(FinancialAccountTypeEnum::class)],
            'is_active'              => ['boolean'],
            'opening_balance_cents'  => ['integer'],
        ];
    }
}
