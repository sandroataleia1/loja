<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFinancialTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'origin_account_id'      => ['required', 'uuid', 'exists:financial_accounts,uuid'],
            'destination_account_id' => ['required', 'uuid', 'exists:financial_accounts,uuid', 'different:origin_account_id'],
            'amount_cents'           => ['required', 'integer', 'min:1'],
            'transferred_at'         => ['nullable', 'date'],
            'description'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
