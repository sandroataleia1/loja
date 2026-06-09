<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCashRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id'  => ['required', 'uuid', 'exists:stores,uuid'],
            'code'      => ['required', 'string', 'max:30'],
            'name'      => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
