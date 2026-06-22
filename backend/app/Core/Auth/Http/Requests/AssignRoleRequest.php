<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use App\Core\Tenancy\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = TenantContext::getIdOrFail();

        return [
            'user_id' => ['required', 'uuid', 'exists:users,uuid'],
            'role_id' => [
                'required',
                'uuid',
                Rule::exists('roles', 'uuid')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true),
            ],
        ];
    }
}
