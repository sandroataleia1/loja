<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PinLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => ['required', 'uuid', 'exists:tenants,uuid'],
            'pin'         => ['required', 'string', 'digits_between:4,6'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }
}
