export type ProductStatus = 'active' | 'inactive'

export interface ProductVariant {
  uuid:         string
  tenant_uuid:  string
  product_uuid: string
  sku:          string
  barcode:      string | null
  attributes:   Record<string, string>  // ex: { size: 'M', color: 'Preto' }
  price:        number | null           // null = herda do produto pai
  cost:         number | null
  stock:        number
  is_active:    boolean
  created_at:   string
  updated_at:   string
}

export interface CreateVariantInput {
  product_uuid: string
  sku:          string
  barcode?:     string
  attributes?:  Record<string, string>
  price?:       number
  cost?:        number
  stock?:       number
}

export interface UpdateVariantInput {
  sku?:        string
  barcode?:    string
  attributes?: Record<string, string>
  price?:      number
  cost?:       number
  stock?:      number
  is_active?:  boolean
}

export interface Product {
  uuid:          string
  tenant_uuid:   string
  category_uuid: string | null
  category_name: string | null
  name:          string
  sku:           string
  barcode:       string | null
  description:   string | null
  price:         number  // centavos
  cost:          number | null
  stock:         number
  status:        ProductStatus
  created_at:    string
  updated_at:    string
}

export interface CreateProductInput {
  category_uuid?: string
  name:           string
  sku:            string
  barcode?:       string
  description?:   string
  price:          number
  cost?:          number
  stock?:         number
  status?:        ProductStatus
}

export interface UpdateProductInput {
  category_uuid?: string
  name?:          string
  sku?:           string
  barcode?:       string
  description?:   string
  price?:         number
  cost?:          number
  stock?:         number
  status?:        ProductStatus
}
