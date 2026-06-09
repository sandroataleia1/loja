export interface CatalogVariant {
  uuid:       string
  sku:        string
  barcode:    string | null
  price:      number | null  // null = inherit product price
  stock:      number
  is_active:  boolean
  attributes: Record<string, string>
}

export interface CatalogProduct {
  uuid:          string
  category_uuid: string | null
  category_name: string | null
  name:          string
  sku:           string
  barcode:       string | null
  description:   string | null
  price:         number  // cents
  stock:         number
  status:        'active' | 'inactive'
  variants:      CatalogVariant[]
}

export interface BarcodeResult {
  product:         CatalogProduct
  matched_variant: string | null  // uuid da variante que tinha o barcode
}

export interface ProductCategory {
  uuid: string
  name: string
}

/** Retorna o preço efetivo de uma variante (fallback ao produto) */
export function variantPrice(product: CatalogProduct, variant: CatalogVariant): number {
  return variant.price ?? product.price
}

/** Gera o nome de exibição de uma variante a partir dos atributos */
export function variantLabel(variant: CatalogVariant): string {
  const vals = Object.values(variant.attributes)
  return vals.length > 0 ? vals.join(' / ') : variant.sku
}
