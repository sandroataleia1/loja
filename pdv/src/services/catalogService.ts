import { invoke } from '@tauri-apps/api/core'
import type { BarcodeResult, CatalogProduct, ProductCategory } from '@/types/catalog'

export const catalogService = {
  /** Carrega todos os produtos ativos com variantes — usado para popular o cache em memória */
  loadAll: (): Promise<CatalogProduct[]> =>
    invoke('catalog_load_all'),

  /** Busca produto (e variante correspondente) por código de barras */
  findBarcode: (barcode: string): Promise<BarcodeResult | null> =>
    invoke('catalog_find_by_barcode', { barcode }),

  /** Lista categorias para os chips de filtro */
  categories: (): Promise<ProductCategory[]> =>
    invoke('catalog_categories'),
}
