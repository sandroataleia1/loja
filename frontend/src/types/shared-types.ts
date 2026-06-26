// ── API Response ───────────────────────────────────────────────────────────

export interface ApiSuccess<T = unknown> {
  success: true
  data:    T
  meta?:   Record<string, unknown>
  message?: string
}

export interface ApiError {
  success: false
  message: string
  errors?: Record<string, string[]>
}

export type ApiResponse<T = unknown> = ApiSuccess<T> | ApiError

export interface PaginationMeta {
  current_page: number
  per_page:     number
  total:        number
  last_page:    number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
}

// ── Tenant ─────────────────────────────────────────────────────────────────

export interface Tenant {
  uuid:        string
  code:        string
  trade_name:  string
  legal_name:  string | null
  document:    string | null
  email:       string | null
  phone:       string | null
  is_active:   boolean
}

// ── Store ──────────────────────────────────────────────────────────────────

export interface Store {
  uuid:        string
  name:        string
  code:        string
  type:        string
  phone:       string | null
  email:       string | null
  address:     Record<string, string> | null
  is_active:   boolean
  is_ecommerce: boolean
  created_at:  string
  updated_at:  string
}

// ── Channel ────────────────────────────────────────────────────────────────

export interface Channel {
  uuid:                 string
  code:                 string
  name:                 string
  type:                 string
  type_label:           string
  is_active:            boolean
  store_id:             string
  requires_credentials: boolean
  is_async_channel:     boolean
  metadata:             Record<string, unknown> | null
  created_at:           string
}

// ── User ───────────────────────────────────────────────────────────────────

export interface User {
  uuid:              string
  name:              string
  email:             string
  role?:             string
  pin?:              string | null
  is_active:         boolean
  email_verified_at: string | null
  last_login_at:     string | null
}

export interface Membership {
  uuid:      string
  tenant_id: string
  role_id:   string
  is_active: boolean
  role?: {
    uuid:        string
    name:        string
    slug:        string
    permissions?: Permission[]
  }
}

// ── Auth ───────────────────────────────────────────────────────────────────

export interface AuthResponse {
  token:   string
  user:    User
  tenant:  Tenant
  store:   Store | null
  channel: Channel | null
}

export interface MeResponse {
  user:        User
  tenant:      Tenant | null
  memberships: Membership[]
  store:       Store | null
  channel:     Channel | null
}

// ── Product Catalog ────────────────────────────────────────────────────────

export type UserRole =
  | 'super_admin'
  | 'admin'
  | 'gerente'
  | 'vendedor'
  | 'caixa'
  | 'estoque'
  | 'financeiro'

export type ProductStatus      = 'draft' | 'active' | 'inactive' | 'archived' | 'seasonal'
export type ProductType        = 'simple' | 'variable' | 'kit'
export type ProductVisibility  = 'PRIVATE' | 'PUBLIC' | 'UNLISTED'
export type UnitOfMeasure      = 'UN' | 'M' | 'M2' | 'M3' | 'KG' | 'LT' | 'CX' | 'SC'
export type AttributeType    = 'text' | 'color' | 'number' | 'boolean'

// SaleStatus e PaymentMethod são definidos na seção "PDV / Sales" (versões
// completas, alinhadas aos enums do backend). As definições duplicadas/antigas
// que existiam aqui foram removidas (quebravam o build com tipos conflitantes).

export interface Category {
  uuid:        string
  code:        string | null
  parent_id:   string | null
  name:        string
  slug:        string
  description: string | null
  sort_order:  number
  is_active:   boolean
  children?:   Category[]
}

export interface Brand {
  uuid:        string
  code:        string | null
  name:        string
  slug:        string
  description: string | null
  logo_url:    string | null
  website_url: string | null
  is_active:   boolean
}

export interface Attribute {
  uuid:               string
  attribute_group_id: string
  value:              string
  label:              string
  color_hex:          string | null
  sort_order:         number
}

export interface AttributeGroup {
  uuid:       string
  name:       string
  slug:       string
  type:       AttributeType
  sort_order: number
  attributes?: Attribute[]
}

export interface GridItem {
  uuid:               string
  attribute_group_id: string
  value:              string
  label:              string
  sort_order:         number
}

export interface Grid {
  uuid:               string
  name:               string
  description:        string | null
  attribute_group_id: string
  attributes?:        GridItem[]
}

export interface ProductMedia {
  uuid?:      string
  product_id: string
  type:       'image' | 'video'
  url:        string
  sort_order: number
  is_primary: boolean
}

export interface ProductBarcode {
  uuid:       string
  value:      string
  type:       string
  type_label: string
  is_primary: boolean
}

export interface Product {
  uuid:              string
  code:              string
  name:              string
  slug:              string
  description:       string | null
  short_description: string | null
  internal_notes:    string | null
  type:                   ProductType
  type_label:             string
  unit_of_measure:        UnitOfMeasure | null
  unit_of_measure_label:  string | null
  ncm:                    string | null
  cest:                   string | null
  cfop_default:           string | null
  origin_code:            number | null
  origin_code_label:      string | null
  status:                 ProductStatus
  status_label:      string
  visibility:        ProductVisibility
  visibility_label:  string
  base_price:        number | null
  cost_price:        number | null
  is_featured:       boolean
  is_digital:        boolean
  is_publishable:    boolean
  location:          string | null
  brand?:            Brand
  brand_id:          string | null
  categories?:       Category[]
  variants?:         ProductVariant[]
  images?:           ProductMedia[]
  barcodes?:         ProductBarcode[]
  created_at:        string
  updated_at:        string
}

export interface ProductVariant {
  uuid:             string
  product_id:       string
  code:             string
  sku:              string
  name:             string | null
  barcode:          string | null
  price_cents:      number
  cost_cents:       number | null
  compare_at_cents: number | null
  sale_price:       number
  cost_price:       number | null
  is_active:        boolean
  is_default:       boolean
  sort_order:       number
  grid_combination: Array<{ group_id: string; group_name: string; attr_id: string; attr_value: string }> | null
  ncm:              string | null
  cest:             string | null
  cfop_default:     string | null
  origin_code:      number | null
  tax_profile_id:   string | null
}

// ── Orders & Quotes ────────────────────────────────────────────────────────

export type QuoteStatus = 'draft' | 'sent' | 'viewed' | 'accepted' | 'rejected' | 'expired' | 'converted' | 'cancelled'
export type OrderStatus = 'pending' | 'confirmed' | 'in_progress' | 'ready' | 'delivered' | 'completed' | 'cancelled'
export type DiscountType = 'fixed' | 'percent'

export interface DocumentItem {
  uuid:               string
  product_variant_id: string | null
  name_snapshot:      string
  sku_snapshot:       string | null
  unit_of_measure:    string
  quantity:           number
  unit_price_cents:   number
  unit_price:         number
  discount_cents:     number
  subtotal_cents:     number
  subtotal:           number
  sort_order:         number
  notes:              string | null
}

export interface DocumentCustomer {
  uuid:  string
  name:  string
  phone: string | null
  email: string | null
}

export interface Quote {
  uuid:                  string
  number:                string
  status:                QuoteStatus
  status_label:          string
  status_color:          string
  store_id:              string | null
  customer_id:           string | null
  customer?:             DocumentCustomer
  seller_id:             string | null
  seller?:               { uuid: string; name: string } | null
  validity_days:         number
  valid_until:           string | null
  is_expired:            boolean
  discount_type:         DiscountType
  discount_value:        number
  discount_cents:        number
  discount:              number
  subtotal_cents:        number
  subtotal:              number
  total_cents:           number
  total:                 number
  notes:                 string | null
  internal_notes:        string | null
  payment_terms:         string | null
  converted_to_order_id: string | null
  converted_at:          string | null
  sent_at:               string | null
  viewed_at:             string | null
  items?:                DocumentItem[]
  items_count?:          number
  created_at:            string
  updated_at:            string
}

export interface Order {
  uuid:                 string
  number:               string
  status:               OrderStatus
  status_label:         string
  status_color:         string
  store_id:             string | null
  customer_id:          string | null
  customer?:            DocumentCustomer
  seller_id:            string | null
  seller?:              { uuid: string; name: string } | null
  quote_id:             string | null
  sale_id:              string | null
  discount_type:        DiscountType
  discount_value:       number
  discount_cents:       number
  discount:             number
  subtotal_cents:       number
  subtotal:             number
  total_cents:          number
  total:                number
  notes:                string | null
  internal_notes:       string | null
  payment_terms:        string | null
  expected_at:          string | null
  confirmed_at:         string | null
  delivered_at:         string | null
  completed_at:         string | null
  cancelled_at:         string | null
  cancellation_reason:  string | null
  allowed_transitions:  Array<{ value: OrderStatus; label: string }>
  items?:               DocumentItem[]
  items_count?:         number
  created_at:           string
  updated_at:           string
}

export interface InventoryLevel {
  store_id:           string
  product_variant_id: string
  quantity:           number
  reserved_quantity:  number
  available_quantity: number
}

// ── RBAC ───────────────────────────────────────────────────────────────────

export interface Permission {
  uuid:        string
  slug:        string   // e.g. 'products.view'
  name:        string
  module:      string   // e.g. 'products'
  description: string | null
}

export interface Role {
  uuid:        string
  name:        string
  slug:        string   // e.g. 'owner', 'manager'
  description: string | null
  is_system:   boolean
  tenant_id:   string | null
  permissions: Permission[]
}

export interface TenantUserData {
  uuid:       string
  tenant_id:  string
  user_id?:   string
  name?:      string
  email?:     string
  username?:  string
  phone?:     string
  has_pin?:   boolean
  role:       Role
  is_active:  boolean
  joined_at:  string
  store_ids:  string[]  // empty = unrestricted
}

export interface StoreAccess {
  tenant_user_id: string
  store_id:       string
}

// ── CRM / Customers ────────────────────────────────────────────────────────

export type PersonType   = 'INDIVIDUAL' | 'COMPANY'
export type ContactType  = 'PHONE' | 'WHATSAPP' | 'EMAIL' | 'INSTAGRAM' | 'OTHER'

export interface CustomerAddress {
  uuid:        string
  customer_id: string
  zipcode:     string
  street:      string
  number:      string
  complement:  string | null
  district:    string
  city:        string
  state:       string
  country:     string
  is_default:  boolean
}

export interface CustomerContact {
  uuid:        string
  customer_id: string
  type:        ContactType
  type_label:  string
  value:       string
  label:       string | null
  is_primary:  boolean
}

export interface CustomerTag {
  uuid:  string
  name:  string
  color: string | null
}

export interface Customer {
  uuid:              string
  code:              string
  person_type:       PersonType
  person_type_label: string
  name:              string
  trade_name:        string | null
  document:          string | null
  email:             string | null
  birth_date:        string | null
  notes:             string | null
  is_active:         boolean
  is_default_consumer: boolean
  last_purchase_at:  string | null
  total_purchases:   number
  total_orders:      number
  addresses?:        CustomerAddress[]
  contacts?:         CustomerContact[]
  tags?:             CustomerTag[]
  created_at:        string
  updated_at:        string
}

// ── Inventory ────────────────────────────────────────────────────────────────

export type MovementType =
  | 'sale' | 'purchase' | 'transfer' | 'return' | 'loss'
  | 'adjustment' | 'inventory' | 'reserve' | 'release'
  | 'in' | 'out'

export type TransferStatus = 'pending' | 'in_transit' | 'received' | 'cancelled'

export interface InventoryBalance {
  uuid:               string
  tenant_id:          string
  store_id:           string
  variant_id:         string
  quantity:           number
  reserved_quantity:  number
  available_quantity: number
  store?: Store
  variant?: {
    uuid:     string
    sku:      string
    name:     string | null
    product?: { uuid: string; name: string; code: string }
  }
}

export interface StockMovement {
  uuid:             string
  tenant_id:        string
  store_id:         string
  variant_id:       string
  type:             MovementType
  type_label?:      string
  quantity:         number
  quantity_before:  number
  quantity_after:   number
  reserved_before:  number
  reserved_after:   number
  reference_type:   string | null
  reference_id:     string | null
  notes:            string | null
  created_at:       string
  store?: Store
  variant?: { uuid: string; sku: string; name: string | null }
}

export interface InventoryAdjustment {
  uuid:              string
  tenant_id:         string
  store_id:          string
  variant_id:        string
  movement_id:       string | null
  previous_quantity: number
  new_quantity:      number
  difference:        number
  reason:            string
  created_by:        string | null
  created_at:        string
  store?: Store
  variant?: { uuid: string; sku: string; name: string | null }
}

export interface StockTransferItem {
  uuid:               string
  transfer_id:        string
  variant_id:         string
  quantity_requested: number
  quantity_sent:      number | null
  quantity_received:  number | null
  variant?: { uuid: string; sku: string; name: string | null }
}

export interface StockTransfer {
  uuid:                 string
  tenant_id:            string
  code?:                string
  origin_store_id:      string
  destination_store_id: string
  status:               TransferStatus
  status_label?:        string
  notes:                string | null
  dispatched_at:        string | null
  received_at:          string | null
  cancelled_at:         string | null
  created_at:           string
  origin_store?:      Store
  destination_store?: Store
  items?: StockTransferItem[]
}

// ── PDV / Sales ──────────────────────────────────────────────────────────────

export type SaleStatus = 'draft' | 'pending' | 'completed' | 'cancelled' | 'partially_refunded' | 'refunded'
export type FiscalStatus = 'pending' | 'issued' | 'cancelled' | 'error'
export type FiscalDocumentStatus = 'pending' | 'processing' | 'authorized' | 'rejected' | 'cancelled' | 'contingency' | 'error'
export type CashSessionStatus = 'open' | 'closed' | 'cancelled'
export type PaymentMethod = 'cash' | 'pix' | 'credit_card' | 'debit_card' | 'store_credit' | 'voucher'
export type FiscalPolicyMode = 'always' | 'never' | 'ask' | 'above_amount'
export type CashMovementType = 'opening' | 'sale' | 'withdrawal' | 'supply' | 'refund' | 'adjustment' | 'closing'

export interface CashRegister {
  uuid: string
  tenant_id: string
  store_id: string
  code: string
  name: string
  is_active: boolean
  store?: Store
}

export interface CashSession {
  uuid: string
  tenant_id: string
  store_id: string
  cash_register_id: string | null
  user_id: string
  status: CashSessionStatus
  opening_amount_cents: number
  closing_amount_cents: number | null
  expected_balance_cents: number | null
  difference_amount_cents: number | null
  notes: string | null
  opened_at: string
  closed_at: string | null
  cash_register?: CashRegister
  store?: Store
}

export interface CashMovement {
  uuid: string
  type: CashMovementType
  amount_cents: number
  description: string
  created_at: string
}

export interface SaleItem {
  uuid: string
  sale_id: string
  product_variant_id: string | null
  sku_snapshot: string
  name_snapshot: string
  quantity: number
  unit_price_cents: number
  discount_amount_cents: number
  total_cents: number
}

export interface PaymentTransaction {
  uuid: string
  sale_id: string
  method: PaymentMethod
  amount_cents: number
  status: string
  paid_at: string | null
}

export interface Sale {
  uuid: string
  code?: string
  tenant_id: string
  store_id: string
  session_id: string | null
  customer_id: string | null
  seller_id: string | null
  status: SaleStatus
  subtotal_cents: number
  discount_total_cents: number
  total_cents: number
  fiscal_status: FiscalStatus
  sales_channel: string
  notes: string | null
  completed_at: string | null
  cancelled_at: string | null
  created_at: string
  updated_at: string
  items?: SaleItem[]
  payments?: PaymentTransaction[]
  customer?: { uuid: string; name: string; document: string | null }
  store?: Store
}

export interface FiscalDocument {
  uuid:          string
  sale_id:       string | null
  type:          string
  type_label:    string
  status:        FiscalDocumentStatus
  status_label:  string
  provider:      string
  access_key:    string | null
  protocol:      string | null
  pdf_path:      string | null
  issued_at:     string | null
  cancelled_at:  string | null
  error_message: string | null
  can_cancel:    boolean
  can_retry:     boolean
  created_at:    string
  updated_at:    string
}

export interface FiscalSettings {
  uuid?: string
  policy_mode: FiscalPolicyMode
  policy_mode_label: string
  min_sale_amount_cents: number
  min_sale_amount: number
  auto_issue_nfce: boolean
  allow_manual_nfce: boolean
  company_name: string | null
  cnpj: string | null
  is_active: boolean
}

export interface TaxProfile {
  uuid: string
  name: string
  regime: string
}

// ── Purchasing ────────────────────────────────────────────────────────────────

export interface Supplier {
  uuid:        string
  code:        string
  person_type: 'INDIVIDUAL' | 'COMPANY'
  name:        string
  trade_name:  string | null
  document:    string | null
  ie:          string | null
  im:          string | null
  email:       string | null
  phone:       string | null
  is_active:   boolean
  notes:       string | null
  addresses?:  unknown[]
  contacts?:   unknown[]
  created_at:  string
  updated_at:  string
}

export type PurchaseOrderStatus = 'draft' | 'sent' | 'partially_received' | 'received' | 'cancelled'

export interface PurchaseOrderItem {
  uuid:                string
  product_variant_id:  string
  quantity:            number
  unit_cost:           number
  total_cost:          number
  received_quantity:   number
  pending_quantity:    number
  variant?:            { uuid: string; sku: string; name: string }
}

export interface PurchaseOrder {
  uuid:                   string
  code:                   string
  status:                 PurchaseOrderStatus
  status_label:           string
  order_date:             string | null
  expected_delivery_date: string | null
  subtotal:               number
  discount:               number
  total:                  number
  notes:                  string | null
  supplier?:              { uuid: string; name: string; code: string }
  items?:                 PurchaseOrderItem[]
  receipts_count?:        number
  created_at:             string
  updated_at:             string
}

