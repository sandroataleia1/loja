<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GrantStoreAccessRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid', 'exists:stores,uuid'],
        ];
    }
}
