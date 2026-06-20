<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Requests;

use App\Modules\Fiscal\Enums\FiscalModeEnum;
use App\Modules\Sales\Enums\PaymentMethodEnum;
use App\Modules\Sales\Enums\SalesChannelEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateSaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id'                          => ['required', 'uuid', 'exists:stores,uuid'],
            'session_id'                        => ['nullable', 'uuid', 'exists:cash_register_sessions,uuid'],
            'customer_id'                       => ['nullable', 'uuid'],
            'seller_id'                         => ['nullable', 'uuid', 'exists:users,uuid'],
            'sync_uuid'                         => ['nullable', 'uuid'],
            'fiscal_mode'                       => ['nullable', new Enum(FiscalModeEnum::class)],
            'sales_channel'                     => ['nullable', new Enum(SalesChannelEnum::class)],
            'notes'                             => ['nullable', 'string', 'max:1000'],
            'metadata'                          => ['nullable', 'array'],

            'items'                             => ['required', 'array', 'min:1'],
            'items.*.variant_id'                => ['nullable', 'uuid', 'exists:catalog_variants,uuid'],
            'items.*.sku_snapshot'              => ['required', 'string', 'max:100'],
            'items.*.name_snapshot'             => ['required', 'string', 'max:255'],
            'items.*.attributes_snapshot'       => ['nullable', 'array'],
            'items.*.quantity'                  => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price_cents'          => ['required', 'integer', 'min:0'],
            'items.*.cost_price_cents'          => ['nullable', 'integer', 'min:0'],
            'items.*.discount_amount_cents'     => ['nullable', 'integer', 'min:0'],

            'payments'                          => ['nullable', 'array'],
            'payments.*.method'                 => ['required_with:payments', new Enum(PaymentMethodEnum::class)],
            'payments.*.amount_cents'           => ['required_with:payments', 'integer', 'min:1'],
            'payments.*.external_reference'     => ['nullable', 'string', 'max:255'],
            'payments.*.notes'                  => ['nullable', 'string', 'max:500'],
            'payments.*.metadata'               => ['nullable', 'array'],
        ];
    }
}
