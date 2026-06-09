<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PayFinancialEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'financial_account_id' => ['nullable', 'uuid', 'exists:financial_accounts,uuid'],
            'amount_cents'         => ['nullable', 'integer', 'min:1'],
            'paid_at'              => ['nullable', 'date'],
            'installment_number'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}
