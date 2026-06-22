<?php

declare(strict_types=1);

namespace App\Modules\Fiscal\Services;

use App\Modules\Catalog\Models\Variant;
use App\Modules\Fiscal\Enums\ProductOriginEnum;

/**
 * Resolve dados fiscais de uma variante com herança do produto pai.
 *
 * Regra de precedência: variant.campo ?? product.campo
 * Quando a variante não tem o campo preenchido, usa o valor do produto.
 * Isso permite definir NCM/CFOP por família no produto e sobrescrever
 * pontualmente em variantes específicas (ex: voltagem diferente = CFOP diferente).
 */
final readonly class FiscalResolverService
{
    /** NCM de 8 dígitos. Null se nem variante nem produto tiverem. */
    public function resolveNcm(Variant $variant): ?string
    {
        $this->ensureProductLoaded($variant);

        return $variant->ncm ?? $variant->product?->ncm;
    }

    /** CEST de 7 dígitos. */
    public function resolveCest(Variant $variant): ?string
    {
        $this->ensureProductLoaded($variant);

        return $variant->cest ?? $variant->product?->cest;
    }

    /** CFOP de saída padrão (4 dígitos). */
    public function resolveCfopDefault(Variant $variant): ?string
    {
        $this->ensureProductLoaded($variant);

        return $variant->cfop_default ?? $variant->product?->cfop_default;
    }

    /** Origem SEFAZ (0 = Nacional … 8). Default: 0. */
    public function resolveOriginCode(Variant $variant): ProductOriginEnum
    {
        $this->ensureProductLoaded($variant);

        return $variant->origin_code
            ?? $variant->product?->origin_code
            ?? ProductOriginEnum::from(0);
    }

    /**
     * Resolve todos os campos fiscais de uma vez.
     *
     * @return array{ncm: ?string, cest: ?string, cfop_default: ?string, origin_code: ProductOriginEnum}
     */
    public function resolveAll(Variant $variant): array
    {
        return [
            'ncm'          => $this->resolveNcm($variant),
            'cest'         => $this->resolveCest($variant),
            'cfop_default' => $this->resolveCfopDefault($variant),
            'origin_code'  => $this->resolveOriginCode($variant),
        ];
    }

    private function ensureProductLoaded(Variant $variant): void
    {
        if (! $variant->relationLoaded('product')) {
            $variant->load('product');
        }
    }
}
