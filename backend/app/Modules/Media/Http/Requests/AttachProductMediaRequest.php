<?php

declare(strict_types=1);

namespace App\Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AttachProductMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'media_asset_id' => ['required', 'uuid', 'exists:media_assets,uuid'],
            'position'       => ['nullable', 'integer', 'min:0'],
            'is_primary'     => ['boolean'],
        ];
    }
}
