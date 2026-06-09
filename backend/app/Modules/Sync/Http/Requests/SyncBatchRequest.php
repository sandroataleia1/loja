<?php

declare(strict_types=1);

namespace App\Modules\Sync\Http\Requests;

use App\Modules\Sync\Enums\SyncEntityTypeEnum;
use App\Modules\Sync\Enums\SyncOperationTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class SyncBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'device_uuid'                        => ['required', 'uuid'],
            'batch_id'                           => ['required', 'string', 'max:36'],

            'operations'                         => ['required', 'array', 'min:1', 'max:500'],
            'operations.*.operation_uuid'        => ['required', 'uuid'],
            'operations.*.entity_type'           => ['required', new Enum(SyncEntityTypeEnum::class)],
            'operations.*.entity_uuid'           => ['required', 'uuid'],
            'operations.*.operation_type'        => ['required', new Enum(SyncOperationTypeEnum::class)],
            'operations.*.idempotency_key'       => ['required', 'string', 'max:100'],
            'operations.*.payload'               => ['required', 'array'],
            'operations.*.created_at'            => ['required', 'date_format:Y-m-d\TH:i:s\Z,Y-m-d\TH:i:sP'],
        ];
    }

    public function messages(): array
    {
        return [
            'operations.max' => 'Máximo de 500 operações por batch.',
        ];
    }
}
