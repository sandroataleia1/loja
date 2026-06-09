<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Enums\AttributeTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAttributeGroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'type'       => ['sometimes', Rule::enum(AttributeTypeEnum::class)],
            'sort_order' => ['integer', 'min:0'],
        ];
    }
}
