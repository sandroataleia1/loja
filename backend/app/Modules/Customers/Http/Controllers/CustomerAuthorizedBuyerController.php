<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAuthorizedBuyer;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerAuthorizedBuyerController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success($customer->authorizedBuyers()->get()->map(fn ($b) => array_merge(
            $b->toArray(),
            ['is_valid' => $b->isValid()],
        )));
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:150'],
            'cpf'                => ['nullable', 'string', 'max:14'],
            'rg'                 => ['nullable', 'string', 'max:20'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'relationship'       => ['nullable', 'string', 'max:80'],
            'credit_limit_cents' => ['nullable', 'integer', 'min:0'],
            'valid_until'        => ['nullable', 'date'],
            'authorized_at'      => ['required', 'date'],
        ]);

        $buyer = CustomerAuthorizedBuyer::create(array_merge(
            [
                'tenant_id'     => $customer->tenant_id,
                'customer_id'   => $customer->uuid,
                'authorized_by' => $request->user()->uuid,
                'is_active'     => true,
            ],
            $validated,
        ));

        return $this->created(array_merge($buyer->toArray(), ['is_valid' => $buyer->isValid()]));
    }

    public function update(Request $request, Customer $customer, CustomerAuthorizedBuyer $authorizedBuyer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'name'               => ['sometimes', 'string', 'max:150'],
            'cpf'                => ['nullable', 'string', 'max:14'],
            'rg'                 => ['nullable', 'string', 'max:20'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'relationship'       => ['nullable', 'string', 'max:80'],
            'credit_limit_cents' => ['nullable', 'integer', 'min:0'],
            'valid_until'        => ['nullable', 'date'],
        ]);

        $authorizedBuyer->update($validated);

        return $this->success(array_merge(
            $authorizedBuyer->refresh()->toArray(),
            ['is_valid' => $authorizedBuyer->isValid()],
        ));
    }

    public function destroy(Request $request, Customer $customer, CustomerAuthorizedBuyer $authorizedBuyer): JsonResponse
    {
        $this->authorize('update', $customer);

        $reason = $request->string('reason', 'Revogado pelo operador')->toString();
        $authorizedBuyer->revoke($reason);
        $authorizedBuyer->delete();

        return $this->noContent();
    }
}
