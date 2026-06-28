<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Http\Controllers;

use App\Core\Tenancy\Services\TenantContext;
use App\Modules\Catalog\Services\PriceResolverService;
use App\Modules\Pricing\Actions\ApplyDiscountAction;
use App\Modules\Pricing\Exceptions\DiscountExceedsLimitException;
use App\Shared\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

final class ApplyDiscountController extends Controller
{
    use HasApiResponse;

    /**
     * POST /api/v1/pricing/apply-discount
     *
     * Calcula o preço final após desconto, validando os limites do usuário.
     *
     * Body: { variant_id, price_list_id?, discount_percent, customer_id? }
     *
     * Útil para PDV calcular desconto em tempo real antes de fechar a venda.
     */
    public function __invoke(
        Request              $request,
        ApplyDiscountAction  $action,
        PriceResolverService $resolver,
    ): JsonResponse {
        $data = $request->validate([
            'variant_id'       => ['required', 'uuid'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'price_list_id'    => ['nullable', 'uuid'],
            'customer_id'      => ['nullable', 'uuid'],
        ]);

        $tenantId   = TenantContext::getIdOrFail();
        $userId     = $request->user()->uuid;

        // Resolve lista de preço: explícita → por cliente → padrão do tenant
        $priceListId = $data['price_list_id']
            ?? $resolver->resolveListForCustomer($tenantId, $data['customer_id'] ?? null);

        $priceCents = $resolver->resolve($tenantId, $data['variant_id'], $priceListId ?? '');

        if ($priceCents === null) {
            return $this->error('Preço não encontrado para esta variante.', status: 422);
        }

        try {
            $result = $action->execute(
                discountPercent:    (float) $data['discount_percent'],
                originalPriceCents: $priceCents,
                tenantId:           $tenantId,
                userId:             $userId,
                priceListId:        $priceListId,
            );
        } catch (DiscountExceedsLimitException $e) {
            return $this->error($e->getMessage(), [
                'requested_percent'   => $e->requested,
                'max_allowed_percent' => $e->maxAllowed,
            ], 422);
        }

        return $this->success($result->toArray());
    }
}
