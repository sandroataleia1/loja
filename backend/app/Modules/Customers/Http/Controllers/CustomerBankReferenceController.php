<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerBankReference;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerBankReferenceController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success($customer->bankReferences()->get());
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'bank_name'                    => ['required', 'string', 'max:80'],
            'bank_agency'                  => ['nullable', 'string', 'max:20'],
            'account_type'                 => ['nullable', 'string', 'in:' . implode(',', CustomerBankReference::ACCOUNT_TYPES)],
            'contact_name'                 => ['nullable', 'string', 'max:100'],
            'phone'                        => ['nullable', 'string', 'max:20'],
            'consulted_at'                 => ['nullable', 'date'],
            'email_1'                      => ['nullable', 'email', 'max:150'],
            'email_2'                      => ['nullable', 'email', 'max:150'],
            'first_purchase_at'            => ['nullable', 'date'],
            'first_purchase_value_cents'   => ['nullable', 'integer', 'min:0'],
            'highest_purchase_value_cents' => ['nullable', 'integer', 'min:0'],
            'last_purchase_at'             => ['nullable', 'date'],
            'last_purchase_value_cents'    => ['nullable', 'integer', 'min:0'],
            'notes'                        => ['nullable', 'string'],
        ]);

        $ref = CustomerBankReference::create(array_merge(
            ['tenant_id' => $customer->tenant_id, 'customer_id' => $customer->uuid],
            $validated,
        ));

        return $this->created($ref);
    }

    public function update(Request $request, Customer $customer, CustomerBankReference $bankReference): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'bank_name'                    => ['sometimes', 'string', 'max:80'],
            'bank_agency'                  => ['nullable', 'string', 'max:20'],
            'account_type'                 => ['nullable', 'string', 'in:' . implode(',', CustomerBankReference::ACCOUNT_TYPES)],
            'contact_name'                 => ['nullable', 'string', 'max:100'],
            'phone'                        => ['nullable', 'string', 'max:20'],
            'consulted_at'                 => ['nullable', 'date'],
            'email_1'                      => ['nullable', 'email', 'max:150'],
            'email_2'                      => ['nullable', 'email', 'max:150'],
            'first_purchase_at'            => ['nullable', 'date'],
            'first_purchase_value_cents'   => ['nullable', 'integer', 'min:0'],
            'highest_purchase_value_cents' => ['nullable', 'integer', 'min:0'],
            'last_purchase_at'             => ['nullable', 'date'],
            'last_purchase_value_cents'    => ['nullable', 'integer', 'min:0'],
            'notes'                        => ['nullable', 'string'],
        ]);

        $bankReference->update($validated);

        return $this->success($bankReference->refresh());
    }

    public function destroy(Customer $customer, CustomerBankReference $bankReference): JsonResponse
    {
        $this->authorize('update', $customer);
        $bankReference->delete();

        return $this->noContent();
    }
}
