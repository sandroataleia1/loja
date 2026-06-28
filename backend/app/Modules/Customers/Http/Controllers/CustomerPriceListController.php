<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Services\PriceResolverService;
use App\Modules\Customers\Http\Resources\CustomerResource;
use App\Modules\Customers\Models\Customer;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

final class CustomerPriceListController extends Controller
{
    use AuthorizesRequests;
    use HasApiResponse;

    /**
     * PATCH /api/v1/customers/{customer}/price-list
     *
     * Vincula ou desvincula uma lista de preços de um cliente.
     * Body: { price_list_id: uuid|null }
     */
    public function update(
        Request              $request,
        Customer             $customer,
        PriceResolverService $resolver,
    ): JsonResponse {
        $this->authorize('update', $customer);

        $tenantId = TenantContext::getIdOrFail();

        $data = $request->validate([
            'price_list_id' => [
                'nullable',
                'uuid',
                Rule::exists('price_lists', 'uuid')->where('tenant_id', $tenantId),
            ],
        ]);

        $customer->update(['price_list_id' => $data['price_list_id']]);

        // Invalida cache de resolução de lista para este cliente
        $resolver->invalidateCustomerList($tenantId, $customer->uuid);

        return $this->success(new CustomerResource($customer->refresh()->load('priceList')));
    }
}
