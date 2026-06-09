<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Requests;

use App\Modules\Fiscal\Enums\FiscalDocumentTypeEnum;
use App\Modules\Fiscal\Enums\FiscalProviderEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class RequestFiscalDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id'        => ['required', 'uuid', 'exists:stores,uuid'],
            'sale_id'         => ['nullable', 'uuid', 'exists:sales,uuid'],
            'type'            => ['required', new Enum(FiscalDocumentTypeEnum::class)],
            'provider'        => ['nullable', new Enum(FiscalProviderEnum::class)],
            'contingency_mode' => ['boolean'],
        ];
    }
}
