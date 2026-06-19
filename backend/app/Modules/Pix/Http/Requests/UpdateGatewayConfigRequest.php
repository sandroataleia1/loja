<?php

declare(strict_types=1);

namespace App\Modules\Pix\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGatewayConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'gateway'           => ['sometimes', Rule::in(['asaas', 'mock'])],
            'api_key'           => ['sometimes', 'nullable', 'string', 'max:512'],
            'environment'       => ['sometimes', Rule::in(['sandbox', 'production'])],
            'is_active'         => ['sometimes', 'boolean'],
            'pix_key'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'pix_key_type'      => ['sometimes', 'nullable', Rule::in(['cpf', 'cnpj', 'email', 'phone', 'random'])],
        ];
    }
}
