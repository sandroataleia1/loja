<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Enums\CustomerStatusEnum;
use App\Modules\Customers\Http\Resources\CustomerResource;
use App\Modules\Customers\Models\Customer;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerBlockController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function block(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $customer->update([
            'status'         => CustomerStatusEnum::Blocked->value,
            'blocked_reason' => $validated['reason'],
            'blocked_at'     => now(),
        ]);

        return $this->success([
            'uuid'           => $customer->uuid,
            'status'         => $customer->status->value,
            'status_label'   => $customer->status->label(),
            'blocked_reason' => $customer->blocked_reason,
            'blocked_at'     => $customer->blocked_at?->toIso8601String(),
        ]);
    }

    public function unblock(Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->update([
            'status'         => CustomerStatusEnum::Active->value,
            'blocked_reason' => null,
            'blocked_at'     => null,
        ]);

        return $this->success([
            'uuid'         => $customer->uuid,
            'status'       => $customer->status->value,
            'status_label' => $customer->status->label(),
        ]);
    }
}
