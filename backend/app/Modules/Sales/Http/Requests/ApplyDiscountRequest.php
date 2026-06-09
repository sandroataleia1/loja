<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Sales\Enums\DiscountTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'         => ['required', Rule::enum(DiscountTypeEnum::class)],
            'percentage'   => ['required_if:type,percentage', 'nullable', 'numeric', 'min:0.01', 'max:100'],
            'amount_cents' => ['required_if:type,fixed', 'nullable', 'integer', 'min:1'],
            'sale_item_id' => ['nullable', 'uuid', 'exists:sale_items,uuid'],
            'reason'       => ['nullable', 'string', 'max:255'],
            'approved_by'  => ['nullable', 'uuid', 'exists:users,uuid'],
        ];
    }
}
