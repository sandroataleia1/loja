<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id'             => ['required', 'uuid', 'exists:stores,uuid'],
            'cash_register_id'     => ['nullable', 'uuid', 'exists:cash_registers,uuid'],
            'opening_amount_cents' => ['integer', 'min:0'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ];
    }
}
