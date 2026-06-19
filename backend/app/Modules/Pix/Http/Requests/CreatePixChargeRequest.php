<?php

declare(strict_types=1);

namespace App\Modules\Pix\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePixChargeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount_cents'       => ['required', 'integer', 'min:1'],
            'description'        => ['sometimes', 'string', 'max:255'],
            'sale_uuid'          => ['sometimes', 'uuid'],
            'expires_in_minutes' => ['sometimes', 'integer', 'min:5', 'max:1440'],
        ];
    }
}
