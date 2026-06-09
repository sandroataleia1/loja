<?php

declare(strict_types=1);

namespace App\Core\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,uuid'],
            'role_id' => ['required', 'uuid', 'exists:roles,uuid'],
        ];
    }
}
