<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AttachImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'imageable_id'   => ['required', 'uuid'],
            'imageable_type' => ['required', 'string', 'max:100'],
            'url'            => ['required', 'url', 'max:1000'],
            'thumbnail_url'  => ['nullable', 'url', 'max:1000'],
            'alt_text'       => ['nullable', 'string', 'max:255'],
            'sort_order'     => ['integer', 'min:0'],
            'is_primary'     => ['boolean'],
            'width'          => ['nullable', 'integer', 'min:1'],
            'height'         => ['nullable', 'integer', 'min:1'],
            'size_bytes'     => ['nullable', 'integer', 'min:0'],
        ];
    }
}
