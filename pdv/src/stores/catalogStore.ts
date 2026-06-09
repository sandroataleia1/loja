import { create } from 'zustand'
import { catalogService } from '@/services/catalogService'
import type { CatalogProduct, ProductCategory } from '@/types/catalog'

interface CatalogStore {
  products:   CatalogProduct[]
  categories: ProductCategory[]
  loading:    boolean
  loadedAt:   number | null

  /** Carrega o catálogo uma única vez (idempotente) */
  load:   () => Promise<void>
  /** Recarrega forçando — use após sync */
  reload: () => Promise<void>
}

export const useCatalogStore = create<CatalogStore>((set, get) => ({
  products:   [],
  categories: [],
  loading:    false,
  loadedAt:   null,

  load: async () => {
    if (get().loadedAt !== null) return
    return get().reload()
  },

  reload: async () => {
    set({ loading: true })
    try {
      const [products, categories] = await Promise.all([
        catalogService.loadAll(),
        catalogService.categories(),
      ])
      set({ products, categories, loading: false, loadedAt: Date.now() })
    } catch (err) {
      set({ loading: false })
      console.error('[catalog] load failed', err)
    }
  },
}))
