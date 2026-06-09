<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'imageable_id'   => ['required', 'uuid'],
            'imageable_type' => ['required', 'string', 'max:100'],
            'uuids'          => ['required', 'array', 'min:1'],
            'uuids.*'        => ['uuid'],
        ];
    }
}
