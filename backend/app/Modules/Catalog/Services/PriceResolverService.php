<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\Variant;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

final readonly class PriceResolverService
{
    private const CACHE_TTL = 300; // 5 minutos

    /**
     * Resolve o preço de uma variante em uma tabela de preços.
     *
     * Ordem de resolução:
     *  1. ProductPrice com variant_id + price_list_id (vigente)
     *  2. ProductPrice com product_id + price_list_id (vigente, sem variant_id)
     *  3. catalog_variants.price_cents (preço base)
     */
    public function resolve(
        string            $tenantId,
        string            $variantId,
        string            $priceListId,
        ?DateTimeInterface $date = null,
    ): ?int {
        $cacheKey = "price:{$tenantId}:{$priceListId}:{$variantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $variantId, $priceListId, $date): ?int {
            $dateStr = ($date ?? now())->format('Y-m-d');

            // 1. Preço específico por variante
            $price = ProductPrice::where('tenant_id', $tenantId)
                ->where('price_list_id', $priceListId)
                ->where('variant_id', $variantId)
                ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $dateStr))
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $dateStr))
                ->value('price_cents');

            if ($price !== null) {
                return $price;
            }

            // 2. Fallback: preço pelo produto pai (sem variant_id específico)
            $variant = Variant::where('uuid', $variantId)->value('product_id');
            if ($variant !== null) {
                $price = ProductPrice::where('tenant_id', $tenantId)
                    ->where('price_list_id', $priceListId)
                    ->where('product_id', $variant)
                    ->whereNull('variant_id')
                    ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $dateStr))
                    ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $dateStr))
                    ->value('price_cents');

                if ($price !== null) {
                    return $price;
                }
            }

            // 3. Fallback final: price_cents base da variante
            return Variant::where('uuid', $variantId)->value('price_cents');
        });
    }

    /** Invalida cache ao atualizar preço. */
    public function invalidate(string $tenantId, string $priceListId, string $variantId): void
    {
        Cache::forget("price:{$tenantId}:{$priceListId}:{$variantId}");
    }
}
