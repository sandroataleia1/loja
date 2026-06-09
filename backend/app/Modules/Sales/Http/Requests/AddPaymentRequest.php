<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'method'             => ['required', Rule::enum(PaymentMethodEnum::class)],
            'amount_cents'       => ['required', 'integer', 'min:1'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'metadata'           => ['nullable', 'array'],
        ];
    }
}
