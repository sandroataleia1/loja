import { useMemo } from 'react'
import { useCatalogStore } from '@/stores/catalogStore'
import type { CatalogProduct } from '@/types/catalog'

/**
 * Busca em memória — sem IPC, sem debounce obrigatório, resultado instantâneo.
 * Combina texto (name/sku/barcode/variantes) com filtro de categoria.
 */
export function useCatalogSearch(
  query:        string,
  categoryUuid?: string | null,
): { results: CatalogProduct[]; loading: boolean } {
  const products = useCatalogStore((s) => s.products)
  const loading  = useCatalogStore((s) => s.loading)

  const results = useMemo(() => {
    let filtered = products

    if (categoryUuid) {
      filtered = filtered.filter((p) => p.category_uuid === categoryUuid)
    }

    const q = query.trim().toLowerCase()
    if (!q) return filtered

    return filtered.filter((p) => {
      if (p.name.toLowerCase().includes(q))    return true
      if (p.sku.toLowerCase().includes(q))     return true
      if (p.barcode?.includes(q))              return true
      return p.variants.some(
        (v) => v.sku.toLowerCase().includes(q) || v.barcode?.includes(q),
      )
    })
  }, [products, query, categoryUuid])

  return { results, loading }
}
