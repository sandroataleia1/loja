<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMovementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'min:1'],
            'description'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
