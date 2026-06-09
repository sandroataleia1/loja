<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlaceOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channel_id'   => ['required', 'uuid', 'exists:channels,uuid'],
            'customer_id'  => ['nullable', 'uuid', 'exists:customers,uuid'],
            'store_id'     => ['nullable', 'uuid', 'exists:stores,uuid'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'placed_at'    => ['nullable', 'date'],
            'metadata'     => ['nullable', 'array'],
            // Line items snapshot for order history
            'metadata.items'    => ['nullable', 'array'],
            'metadata.shipping' => ['nullable', 'array'],
            'metadata.notes'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
