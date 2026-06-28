<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerGuarantor;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerGuarantorController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success($customer->guarantors()->get());
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'guarantor_type'  => ['required', 'string', 'in:person,company'],
            'name'            => ['required', 'string', 'max:150'],
            'document'        => ['required', 'string', 'max:20'],
            'rg'              => ['nullable', 'string', 'max:20'],
            'profession'      => ['nullable', 'string', 'max:100'],
            'employer'        => ['nullable', 'string', 'max:150'],
            'monthly_income'  => ['nullable', 'numeric', 'min:0'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:150'],
            'zip_code'        => ['nullable', 'string', 'max:9'],
            'street'          => ['nullable', 'string', 'max:200'],
            'number'          => ['nullable', 'string', 'max:20'],
            'complement'      => ['nullable', 'string', 'max:100'],
            'neighborhood'    => ['nullable', 'string', 'max:100'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'size:2'],
            'relationship'               => ['nullable', 'string', 'max:80'],
            'notes'                      => ['nullable', 'string'],
            // BLOCO 10 — campos complementares
            'birth_date'                  => ['nullable', 'date'],
            'marital_status'              => ['nullable', 'string', 'in:single,married,divorced,widowed,stable_union,separated'],
            'years_at_address'            => ['nullable', 'integer', 'min:0', 'max:99'],
            'housing_type'                => ['nullable', 'string', 'in:own,rented,financed,family'],
            'other_income'                => ['nullable', 'numeric', 'min:0'],
            'assets_description'          => ['nullable', 'string'],
            'is_same_address_as_customer' => ['nullable', 'boolean'],
        ]);

        $guarantor = CustomerGuarantor::create(array_merge(
            ['tenant_id' => $customer->tenant_id, 'customer_id' => $customer->uuid],
            $validated,
        ));

        return $this->created($guarantor);
    }

    public function show(Customer $customer, CustomerGuarantor $guarantor): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success($guarantor);
    }

    public function update(Request $request, Customer $customer, CustomerGuarantor $guarantor): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'guarantor_type'  => ['sometimes', 'required', 'string', 'in:person,company'],
            'name'            => ['sometimes', 'required', 'string', 'max:150'],
            'document'        => ['sometimes', 'required', 'string', 'max:20'],
            'rg'              => ['nullable', 'string', 'max:20'],
            'profession'      => ['nullable', 'string', 'max:100'],
            'employer'        => ['nullable', 'string', 'max:150'],
            'monthly_income'  => ['nullable', 'numeric', 'min:0'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'email'           => ['nullable', 'email', 'max:150'],
            'zip_code'        => ['nullable', 'string', 'max:9'],
            'street'          => ['nullable', 'string', 'max:200'],
            'number'          => ['nullable', 'string', 'max:20'],
            'complement'      => ['nullable', 'string', 'max:100'],
            'neighborhood'    => ['nullable', 'string', 'max:100'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'size:2'],
            'relationship'               => ['nullable', 'string', 'max:80'],
            'notes'                      => ['nullable', 'string'],
            // BLOCO 10
            'birth_date'                  => ['nullable', 'date'],
            'marital_status'              => ['nullable', 'string', 'in:single,married,divorced,widowed,stable_union,separated'],
            'years_at_address'            => ['nullable', 'integer', 'min:0', 'max:99'],
            'housing_type'                => ['nullable', 'string', 'in:own,rented,financed,family'],
            'other_income'                => ['nullable', 'numeric', 'min:0'],
            'assets_description'          => ['nullable', 'string'],
            'is_same_address_as_customer' => ['nullable', 'boolean'],
        ]);

        $guarantor->update($validated);

        return $this->success($guarantor->refresh());
    }

    public function destroy(Customer $customer, CustomerGuarantor $guarantor): JsonResponse
    {
        $this->authorize('update', $customer);

        $guarantor->delete();

        return $this->noContent();
    }
}
