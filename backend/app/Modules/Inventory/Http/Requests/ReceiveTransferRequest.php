<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReceiveTransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.variant_id'             => ['required', 'uuid'],
            'items.*.quantity_received'      => ['required', 'integer', 'min:0'],
            'notes'                          => ['nullable', 'string', 'max:500'],
        ];
    }
}
