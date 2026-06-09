<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCollectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_url'   => ['nullable', 'url', 'max:500'],
            'starts_at'   => ['nullable', 'date'],
            'ends_at'     => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active'   => ['boolean'],
        ];
    }
}
