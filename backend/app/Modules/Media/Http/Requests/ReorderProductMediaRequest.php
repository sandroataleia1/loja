<?php

declare(strict_types=1);

namespace App\Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderProductMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order'    => ['required', 'array', 'min:1'],
            'order.*'  => ['required', 'integer', 'exists:media_assets,id'],
        ];
    }
}
