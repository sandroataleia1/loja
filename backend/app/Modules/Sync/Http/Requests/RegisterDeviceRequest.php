<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id'    => ['required', 'uuid', 'exists:stores,uuid'],
            'device_uuid' => ['required', 'uuid'],
            'name'        => ['required', 'string', 'max:100'],
            'platform'    => ['nullable', 'string', 'in:windows,android,ios,web'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
