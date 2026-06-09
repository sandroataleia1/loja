<?php

declare(strict_types=1);

namespace App\Modules\Media\Http\Requests;

use App\Modules\Media\Enums\MediaTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreMediaAssetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,gif,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf',
            ],
            'type'          => ['required', new Enum(MediaTypeEnum::class)],
            'original_name' => ['nullable', 'string', 'max:255'],
            'metadata'      => ['nullable', 'array'],
        ];
    }
}
