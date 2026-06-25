<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\UnitConversion;

/**
 * Resolve conversões de unidade de medida.
 *
 * Prioridade de resolução (mais específico primeiro):
 *   1. variant_id correspondente
 *   2. product_id correspondente (variant_id = null)
 *   3. Global no tenant (product_id = null, variant_id = null)
 *
 * Exemplo:
 *   convert(5, 'CX', 'UN', productId: 'abc') → 60 (1 CX = 12 UN)
 *   convert(10, 'M2', 'CX', variantId: 'xyz') → 6.17 (1 M² piso 60×60 = 0.617 cx)
 */
final class UnitConversionService
{
    /**
     * Converte uma quantidade de fromUnit para toUnit.
     *
     * @return float|null null quando não há fator de conversão cadastrado
     */
    public function convert(
        float $quantity,
        string $fromUnit,
        string $toUnit,
        ?string $productId = null,
        ?string $variantId = null,
    ): ?float {
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        $factor = $this->getConversionFactor($fromUnit, $toUnit, $productId, $variantId);

        if ($factor === null) {
            return null;
        }

        return round($quantity * $factor, 6);
    }

    /**
     * Retorna o fator de conversão de fromUnit para toUnit.
     *
     * Tenta também a conversão inversa (de toUnit para fromUnit) e aplica 1/factor.
     */
    public function getConversionFactor(
        string $fromUnit,
        string $toUnit,
        ?string $productId = null,
        ?string $variantId = null,
    ): ?float {
        // Tentativa direta: from → to
        $conversion = $this->findConversion($fromUnit, $toUnit, $productId, $variantId);
        if ($conversion !== null) {
            return $conversion->factor;
        }

        // Tentativa inversa: to → from (fator = 1/factor)
        $inverse = $this->findConversion($toUnit, $fromUnit, $productId, $variantId);
        if ($inverse !== null && $inverse->factor != 0) {
            return round(1 / $inverse->factor, 6);
        }

        return null;
    }

    /**
     * Retorna todas as conversões ativas para um produto/variante ou globais.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, UnitConversion>
     */
    public function getConversionsFor(?string $productId = null, ?string $variantId = null): \Illuminate\Database\Eloquent\Collection
    {
        return UnitConversion::query()
            ->where('is_active', true)
            ->where(function ($q) use ($productId, $variantId): void {
                $q->whereNull('product_id')
                  ->orWhere('product_id', $productId);

                if ($variantId !== null) {
                    $q->orWhere('variant_id', $variantId);
                }
            })
            ->orderByRaw('CASE WHEN variant_id IS NOT NULL THEN 1 WHEN product_id IS NOT NULL THEN 2 ELSE 3 END')
            ->get();
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private function findConversion(
        string $fromUnit,
        string $toUnit,
        ?string $productId,
        ?string $variantId,
    ): ?UnitConversion {
        // 1. Específico por variante
        if ($variantId !== null) {
            $conv = UnitConversion::query()
                ->where('from_unit', $fromUnit)
                ->where('to_unit', $toUnit)
                ->where('variant_id', $variantId)
                ->where('is_active', true)
                ->first();

            if ($conv !== null) {
                return $conv;
            }
        }

        // 2. Específico por produto
        if ($productId !== null) {
            $conv = UnitConversion::query()
                ->where('from_unit', $fromUnit)
                ->where('to_unit', $toUnit)
                ->where('product_id', $productId)
                ->whereNull('variant_id')
                ->where('is_active', true)
                ->first();

            if ($conv !== null) {
                return $conv;
            }
        }

        // 3. Global (product_id = null e variant_id = null)
        return UnitConversion::query()
            ->where('from_unit', $fromUnit)
            ->where('to_unit', $toUnit)
            ->whereNull('product_id')
            ->whereNull('variant_id')
            ->where('is_active', true)
            ->first();
    }
}
