import { useCallback } from 'react'
import { useCatalogStore } from '@/stores/catalogStore'
import { catalogService } from '@/services/catalogService'
import type { BarcodeResult } from '@/types/catalog'

/**
 * Busca por código de barras — tenta cache em memória primeiro,
 * faz fallback para SQLite se não encontrar (produto novo desde o load).
 */
export function useBarcode() {
  const products = useCatalogStore((s) => s.products)

  const findByBarcode = useCallback(
    async (barcode: string): Promise<BarcodeResult | null> => {
      // 1. Cache em memória (instantâneo)
      for (const p of products) {
        if (p.barcode === barcode) {
          return { product: p, matched_variant: null }
        }
        const v = p.variants.find((v) => v.barcode === barcode)
        if (v) {
          return { product: p, matched_variant: v.uuid }
        }
      }

      // 2. Fallback SQLite (cache miss — produto adicionado após startup)
      return catalogService.findBarcode(barcode)
    },
    [products],
  )

  return { findByBarcode }
}
