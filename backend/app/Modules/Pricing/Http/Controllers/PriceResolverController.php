<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Services\PriceResolverService;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class PriceResolverController extends Controller
{
    use HasApiResponse;

    /**
     * GET /api/v1/pricing/resolve?customer_id={uuid}&variant_id={uuid}
     *
     * Resolve o preço de uma variante para um cliente específico.
     * Determina automaticamente qual lista de preço usar.
     *
     * Response: { price_list_id, price_cents, min_price_cents,
     *             packaging_price_cents, packaging_qty }
     */
    public function resolve(Request $request, PriceResolverService $resolver): JsonResponse
    {
        $data = $request->validate([
            'variant_id'  => ['required', 'uuid'],
            'customer_id' => ['nullable', 'uuid'],
        ]);

        $tenantId    = TenantContext::getIdOrFail();
        $variantId   = $data['variant_id'];
        $customerId  = $data['customer_id'] ?? null;

        $priceListId = $resolver->resolveListForCustomer($tenantId, $customerId);

        if ($priceListId === null) {
            return $this->error('Nenhuma lista de preço padrão configurada para este tenant.', status: 422);
        }

        $priceCents = $resolver->resolve($tenantId, $variantId, $priceListId);

        // Busca metadados complementares (min_price, embalagem) sem cache extra
        $meta = ProductPrice::where('tenant_id', $tenantId)
            ->where('price_list_id', $priceListId)
            ->where(fn ($q) => $q->where('variant_id', $variantId)->orWhere(
                fn ($q2) => $q2->whereNull('variant_id')
                    ->whereHas('variant', fn ($q3) => $q3->where('uuid', $variantId))
            ))
            ->orderByRaw("CASE WHEN variant_id IS NOT NULL THEN 0 ELSE 1 END")
            ->first(['min_price_cents', 'packaging_price_cents', 'packaging_qty']);

        return $this->success([
            'price_list_id'         => $priceListId,
            'price_cents'           => $priceCents,
            'min_price_cents'       => $meta?->min_price_cents,
            'packaging_price_cents' => $meta?->packaging_price_cents,
            'packaging_qty'         => $meta?->packaging_qty,
        ]);
    }
}
