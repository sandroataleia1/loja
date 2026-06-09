<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PublishProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id'         => ['required', 'uuid', 'exists:catalog_products,uuid'],
            'metadata_overrides' => ['nullable', 'array'],
            // per-channel copy overrides (title, description, hashtags, etc.)
            'metadata_overrides.title'       => ['nullable', 'string', 'max:255'],
            'metadata_overrides.description' => ['nullable', 'string', 'max:5000'],
            'metadata_overrides.tags'        => ['nullable', 'array'],
        ];
    }
}
