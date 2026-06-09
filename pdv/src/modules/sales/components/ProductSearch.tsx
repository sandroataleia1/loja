import { useCallback } from 'react'
import { useCartStore } from '@/stores/cartStore'
import { QuickSearch }  from '@/modules/catalog/components/QuickSearch'
import type { CartableProduct } from '@/stores/cartStore'

export function ProductSearch() {
  const addItem = useCartStore((s) => s.addItem)

  const handleAdd = useCallback(
    (product: CartableProduct, qty: number, variantUuid?: string | null, attributes?: Record<string, string>) => {
      for (let i = 0; i < qty; i++) {
        addItem(product, variantUuid, attributes)
      }
    },
    [addItem],
  )

  return <QuickSearch onAdd={handleAdd} />
}
