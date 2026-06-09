<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelFiscalDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
