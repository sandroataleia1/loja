<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email:rfc'],
            'password'    => ['required', 'string', 'min:8'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
