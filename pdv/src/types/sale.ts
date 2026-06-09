export type PaymentMethod = 'cash' | 'card_debit' | 'card_credit' | 'pix' | 'voucher'
export type SaleStatus    = 'completed' | 'cancelled' | 'pending'

export interface CartItem {
  productUuid:     string
  variantUuid?:    string | null  // variante selecionada (moda: tamanho/cor)
  productName:     string
  productSku:      string
  attributesJson?: string         // JSON snapshot de atributos da variante
  cost?:           number         // custo em centavos (para snapshot histórico)
  quantity:        number
  unitPrice:       number  // centavos
  discount:        number  // centavos
  total:           number  // centavos
}

export interface CartPayment {
  method:    PaymentMethod
  amount:    number
  reference: string
}

export interface SaleItem {
  uuid:                 string
  sale_uuid:            string
  product_uuid:         string
  variant_uuid:         string | null
  product_name:         string
  product_sku:          string
  name_snapshot:        string
  sku_snapshot:         string
  attributes_snapshot:  string
  cost_snapshot:        number | null
  quantity:             number
  unit_price:           number
  discount:             number
  total:                number
}

export interface Payment {
  uuid:      string
  sale_uuid: string
  method:    string
  amount:    number
  reference: string | null
}

export interface Sale {
  uuid:          string
  tenant_uuid:   string
  store_uuid:    string
  customer_uuid: string | null
  customer_name: string | null
  user_uuid:     string
  status:        SaleStatus
  subtotal:      number
  discount:      number
  total:         number
  notes:         string | null
  completed_at:  string | null
  synced_at:     string | null
  created_at:    string
}

export interface SaleDetail {
  sale:     Sale
  items:    SaleItem[]
  payments: Payment[]
}

export interface CreateSaleItemInput {
  product_uuid:          string
  variant_uuid?:         string | null
  product_name:          string
  product_sku:           string
  name_snapshot?:        string
  sku_snapshot?:         string
  attributes_snapshot?:  string
  cost_snapshot?:        number | null
  quantity:              number
  unit_price:            number
  discount?:             number
}

export interface CreatePaymentInput {
  method:     string
  amount:     number
  reference?: string
}

export interface CreateSaleInput {
  customer_uuid?: string
  user_uuid:      string
  discount?:      number
  notes?:         string
  items:          CreateSaleItemInput[]
  payments:       CreatePaymentInput[]
}
