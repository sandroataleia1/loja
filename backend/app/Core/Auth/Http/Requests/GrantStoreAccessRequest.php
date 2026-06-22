<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GrantStoreAccessRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'store_id' => [
                'required',
                'uuid',
                Rule::exists('stores', 'uuid')
                    ->where('tenant_id', TenantContext::getIdOrFail()),
            ],
        ];
    }
}
