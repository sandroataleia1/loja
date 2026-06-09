<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinancialEntryTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateFinancialEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'                  => ['required', new Enum(FinancialEntryTypeEnum::class)],
            'category_id'           => ['nullable', 'uuid', 'exists:financial_categories,uuid'],
            'financial_account_id'  => ['nullable', 'uuid', 'exists:financial_accounts,uuid'],
            'amount_cents'          => ['required', 'integer', 'min:1'],
            'due_date'              => ['required', 'date'],
            'description'           => ['nullable', 'string', 'max:500'],
            'reference_type'        => ['nullable', 'string', 'max:50'],
            'reference_id'          => ['nullable', 'uuid'],
            'external_reference'    => ['nullable', 'string', 'max:100'],
            'store_id'              => ['nullable', 'uuid', 'exists:stores,uuid'],
            'installments'          => ['nullable', 'array', 'min:2'],
            'installments.*.due_date'     => ['required_with:installments', 'date'],
            'installments.*.amount_cents' => ['required_with:installments', 'integer', 'min:1'],
        ];
    }
}
