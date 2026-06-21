<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Orders\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrderStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status'               => ['required', Rule::enum(OrderStatusEnum::class)],
            'cancellation_reason'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
