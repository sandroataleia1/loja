<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerAsset;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerAssetController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    public function index(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success($customer->assets()->get());
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'asset_type'            => ['required', 'string', 'in:' . implode(',', CustomerAsset::TYPES)],
            'description'           => ['required', 'string', 'max:200'],
            'address'               => ['nullable', 'string', 'max:300'],
            'estimated_value_cents' => ['nullable', 'integer', 'min:0'],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        $asset = CustomerAsset::create(array_merge(
            ['tenant_id' => $customer->tenant_id, 'customer_id' => $customer->uuid],
            $validated,
        ));

        return $this->created($asset);
    }

    public function update(Request $request, Customer $customer, CustomerAsset $asset): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'asset_type'            => ['sometimes', 'string', 'in:' . implode(',', CustomerAsset::TYPES)],
            'description'           => ['sometimes', 'string', 'max:200'],
            'address'               => ['nullable', 'string', 'max:300'],
            'estimated_value_cents' => ['nullable', 'integer', 'min:0'],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        $asset->update($validated);

        return $this->success($asset->refresh());
    }

    public function destroy(Customer $customer, CustomerAsset $asset): JsonResponse
    {
        $this->authorize('update', $customer);
        $asset->delete();

        return $this->noContent();
    }
}
