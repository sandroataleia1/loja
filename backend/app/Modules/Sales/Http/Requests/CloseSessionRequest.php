<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CloseSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'closing_amount_cents' => ['required', 'integer', 'min:0'],
            'difference_reason'    => ['nullable', 'string', 'max:500'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ];
    }
}
