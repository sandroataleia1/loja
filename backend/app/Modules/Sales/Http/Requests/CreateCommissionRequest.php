<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCommissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'    => ['required', 'uuid', 'exists:users,uuid'],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }
}
