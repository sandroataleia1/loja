<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Requests;

use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class SyncPullRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'device_uuid'   => ['required', 'uuid'],
            'batch_id'      => ['required', 'string', 'max:36'],

            'entity_types'  => ['required', 'array', 'min:1'],
            'entity_types.*' => [new Enum(SyncEntityTypeEnum::class)],

            // checkpoints: { "product": "2025-01-01T00:00:00Z", ... }
            'checkpoints'   => ['nullable', 'array'],
            'checkpoints.*' => ['nullable', 'date'],
        ];
    }
}
