<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Enums\ReturnReasonEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class ProcessReturnRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'original_sale_id'               => ['required', 'uuid', 'exists:sales,uuid'],
            'reason'                          => ['required', new Enum(ReturnReasonEnum::class)],
            'return_type'                     => ['required', 'in:return,exchange'],
            'refund_method'                   => ['nullable', 'string'],
            'notes'                           => ['nullable', 'string', 'max:1000'],

            'items'                           => ['required', 'array', 'min:1'],
            'items.*.sale_item_id'            => ['required', 'uuid', 'exists:sale_items,uuid'],
            'items.*.quantity_returned'       => ['required', 'integer', 'min:1'],
            'items.*.condition'               => ['nullable', 'in:good,damaged,defective'],
            'items.*.condition_notes'         => ['nullable', 'string', 'max:500'],
        ];
    }
}
