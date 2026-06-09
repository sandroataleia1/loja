<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateChannelPriceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id'        => ['required', 'uuid', 'exists:catalog_products,uuid'],
            'price'             => ['required', 'numeric', 'min:0'],
            'promotional_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'starts_at'         => ['nullable', 'date'],
            'ends_at'           => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
