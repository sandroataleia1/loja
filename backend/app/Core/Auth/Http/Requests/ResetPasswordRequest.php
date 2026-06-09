<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'email:rfc'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }
}
