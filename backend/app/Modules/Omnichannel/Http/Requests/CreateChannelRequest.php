<?php

declare(strict_types=1);

namespace App\Modules\Omnichannel\Http\Requests;

use App\Modules\Omnichannel\Enums\ChannelTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateChannelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'type'      => ['required', 'string', new Enum(ChannelTypeEnum::class)],
            'is_active' => ['boolean'],
            'store_id'  => ['nullable', 'uuid', 'exists:stores,uuid'],
            'metadata'  => ['nullable', 'array'],
        ];
    }
}
