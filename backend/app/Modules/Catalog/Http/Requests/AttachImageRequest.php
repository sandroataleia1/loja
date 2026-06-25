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
            // Aceita arquivo multipart OU URL — pelo menos um é obrigatório
            'file'           => ['required_without:url', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
            'url'            => ['required_without:file', 'nullable', 'url', 'max:1000'],
            'thumbnail_url'  => ['nullable', 'url', 'max:1000'],
            'alt_text'       => ['nullable', 'string', 'max:255'],
            'sort_order'     => ['integer', 'min:0'],
            'is_primary'     => ['boolean'],
        ];
    }
}
