// Tipos para carrinhos persistidos no SQLite (on-hold).
// Diferente do CartItem em sale.ts que é o carrinho em memória (Zustand).

export type CartStatus = 'on_hold' | 'converted' | 'cancelled'

export interface Cart {
  uuid:          string
  tenant_uuid:   string
  store_uuid:    string
  user_uuid:     string
  customer_uuid: string | null
  status:        CartStatus
  notes:         string | null
  created_at:    string
  updated_at:    string
}

export interface SavedCartItem {
  uuid:         string
  cart_uuid:    string
  product_uuid: string
  variant_uuid: string | null
  product_name: string
  product_sku:  string
  quantity:     number
  unit_price:   number
  discount:     number
  total:        number
  created_at:   string
  updated_at:   string
}

export interface CartWithItems {
  cart:  Cart
  items: SavedCartItem[]
}

export interface SaveCartItemInput {
  product_uuid: string
  variant_uuid?: string
  product_name: string
  product_sku:  string
  quantity:     number
  unit_price:   number
  discount?:    number
}

export interface SaveCartInput {
  customer_uuid?: string
  notes?:         string
  items:          SaveCartItemInput[]
}
