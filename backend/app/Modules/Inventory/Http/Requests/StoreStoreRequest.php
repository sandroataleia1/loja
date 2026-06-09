<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'code'         => ['required', 'string', 'max:20'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:150'],
            'address'      => ['nullable', 'array'],
            'is_active'    => ['boolean'],
            'is_ecommerce' => ['boolean'],
        ];
    }
}
