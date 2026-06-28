<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\PriceList;
use App\Modules\Catalog\Models\ProductPrice;
use App\Modules\Catalog\Models\Variant;
use App\Modules\Customers\Models\Customer;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

final readonly class PriceResolverService
{
    private const CACHE_TTL      = 300;  // 5 minutos — preço por variante
    private const LIST_CACHE_TTL = 600;  // 10 minutos — lista por cliente / padrão

    /**
     * Resolve o preço de uma variante em uma tabela de preços.
     *
     * Ordem de resolução:
     *  1. ProductPrice com variant_id + price_list_id (vigente)
     *  2. ProductPrice com product_id + price_list_id (vigente, sem variant_id) — 1 query com JOIN
     *  3. catalog_variants.price_cents (preço base)
     */
    public function resolve(
        string             $tenantId,
        string             $variantId,
        string             $priceListId,
        ?DateTimeInterface $date = null,
    ): ?int {
        $cacheKey = "price:{$tenantId}:{$priceListId}:{$variantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId, $variantId, $priceListId, $date, $cacheKey): ?int {
            // Rastreia a chave no set da lista para permitir invalidação em lote
            $keysIndex = "price_keys:{$tenantId}:{$priceListId}";
            Cache::forever($keysIndex, array_unique(array_merge(
                Cache::get($keysIndex, []),
                [$cacheKey],
            )));

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

            // 2. Fallback: preço pelo produto pai — 1 query com JOIN em catalog_variants
            // Lógica: variante → product_id → ProductPrice onde product_id = variante.product_id e sem variant_id
            $price = ProductPrice::where('product_prices.tenant_id', $tenantId)
                ->where('product_prices.price_list_id', $priceListId)
                ->whereNull('product_prices.variant_id')
                ->join('catalog_variants', 'catalog_variants.product_id', '=', 'product_prices.product_id')
                ->where('catalog_variants.uuid', $variantId)
                ->where(fn ($q) => $q->whereNull('product_prices.valid_from')->orWhere('product_prices.valid_from', '<=', $dateStr))
                ->where(fn ($q) => $q->whereNull('product_prices.valid_to')->orWhere('product_prices.valid_to', '>=', $dateStr))
                ->value('product_prices.price_cents');

            if ($price !== null) {
                return $price;
            }

            // 3. Fallback final: price_cents base da variante
            return Variant::where('uuid', $variantId)->value('price_cents');
        });
    }

    /**
     * Retorna o UUID da lista de preços que deve ser usada para um cliente.
     *
     * Ordem:
     *  1. price_list_id do próprio cliente (se vinculado)
     *  2. Lista padrão ativa do tenant
     */
    public function resolveListForCustomer(string $tenantId, ?string $customerId): ?string
    {
        if ($customerId !== null) {
            $listId = Cache::remember(
                "customer_pricelist:{$tenantId}:{$customerId}",
                self::LIST_CACHE_TTL,
                fn () => Customer::where('tenant_id', $tenantId)
                    ->where('uuid', $customerId)
                    ->value('price_list_id'),
            );

            if ($listId !== null) {
                return $listId;
            }
        }

        return Cache::remember(
            "default_pricelist:{$tenantId}",
            self::LIST_CACHE_TTL,
            fn () => PriceList::where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->value('uuid'),
        );
    }

    /** Invalida cache de um preço específico (variante × lista). */
    public function invalidate(string $tenantId, string $priceListId, string $variantId): void
    {
        Cache::forget("price:{$tenantId}:{$priceListId}:{$variantId}");
    }

    /** Invalida todas as chaves de preço de uma lista inteira. */
    public function invalidateList(string $tenantId, string $priceListId): void
    {
        $keysIndex = "price_keys:{$tenantId}:{$priceListId}";
        $keys      = Cache::get($keysIndex, []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget($keysIndex);
    }

    /** Invalida cache de resolução de lista para um cliente. */
    public function invalidateCustomerList(string $tenantId, string $customerId): void
    {
        Cache::forget("customer_pricelist:{$tenantId}:{$customerId}");
    }

    /** Invalida cache da lista padrão do tenant. */
    public function invalidateDefaultList(string $tenantId): void
    {
        Cache::forget("default_pricelist:{$tenantId}");
    }
}
