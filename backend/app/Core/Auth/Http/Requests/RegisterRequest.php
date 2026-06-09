<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_name'           => ['required', 'string', 'min:2', 'max:150'],
            'legal_name'            => ['sometimes', 'nullable', 'string', 'max:200'],
            'document'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'phone'                 => ['sometimes', 'nullable', 'string', 'max:30'],
            'name'                  => ['required', 'string', 'min:2', 'max:150'],
            'email'                 => ['required', 'email:rfc', 'max:150'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'device_name'           => ['sometimes', 'string', 'max:255'],
        ];
    }
}
