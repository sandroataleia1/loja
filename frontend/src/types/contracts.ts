import type {
  ProductType,
  ProductStatus,
  ProductVisibility,
  UnitOfMeasure,
  AttributeType,
  ContactType,
  PersonType,
  FiscalPolicyMode,
} from './shared-types'

// Re-exports shared domain types
export type {
  ApiError,
  ApiResponse,
  ApiSuccess,
  PaginationMeta,
  PaginatedResponse,
  Tenant,
  Store,
  Channel,
  User,
  Membership,
  AuthResponse,
  MeResponse,
  UserRole,
  ProductStatus,
  ProductType,
  ProductVisibility,
  UnitOfMeasure,
  AttributeType,
  SaleStatus,
  PaymentMethod,
  Category,
  Brand,
  Attribute,
  AttributeGroup,
  GridItem,
  Grid,
  ProductMedia,
  Product,
  ProductVariant,
  InventoryLevel,
  Permission,
  Role,
  TenantUserData,
  StoreAccess,
  PersonType,
  ContactType,
  CustomerAddress,
  CustomerContact,
  CustomerTag,
  Customer,
  MovementType,
  TransferStatus,
  InventoryBalance,
  StockMovement,
  InventoryAdjustment,
  StockTransferItem,
  StockTransfer,
  FiscalStatus,
  FiscalDocumentStatus,
  CashSessionStatus,
  FiscalPolicyMode,
  CashMovementType,
  CashRegister,
  CashSession,
  CashMovement,
  SaleItem,
  PaymentTransaction,
  Sale,
  FiscalDocument,
  FiscalSettings,
  TaxProfile,
} from './shared-types'

// ── Auth contracts ─────────────────────────────────────────────────────────

export interface LoginRequest {
  email:       string
  password:    string
  device_name?: string
}

export interface RegisterRequest {
  tenant_name:            string
  name:                   string
  email:                  string
  password:               string
  password_confirmation:  string
  legal_name?:            string
  document?:              string
  phone?:                 string
  device_name?:           string
}

export interface ForgotPasswordRequest {
  email: string
}

export interface ResetPasswordRequest {
  token:                 string
  email:                 string
  password:              string
  password_confirmation: string
}

// ── Product contracts ──────────────────────────────────────────────────────

export interface ProductBarcodeInput {
  value:      string
  type:       string
  is_primary: boolean
}

export interface CreateProductRequest {
  name:               string
  type?:              ProductType
  unit_of_measure?:   UnitOfMeasure | null
  status?:            ProductStatus
  visibility?:        ProductVisibility
  brand_id?:          string | null
  category_uuids?:    string[]
  description?:       string | null
  short_description?: string | null
  internal_notes?:    string | null
  base_price?:        number
  cost_price?:        number
  is_featured?:       boolean
  is_digital?:        boolean
  is_publishable?:    boolean
  location?:          string | null
  barcodes?:          ProductBarcodeInput[]
  // Fiscal
  ncm?:               string | null
  cest?:              string | null
  cfop_default?:      string | null
  origin_code?:       number | null
}

// ── RBAC contracts ─────────────────────────────────────────────────────────

export interface CreateRoleRequest {
  name:         string
  description?: string
}

export interface UpdateRoleRequest {
  name?:        string
  description?: string
}

export interface AssignRoleRequest {
  user_id: string
  role_id: string
}

export interface SyncPermissionsRequest {
  permission_ids: string[]
}

export interface GrantStoreAccessRequest {
  store_id: string
}

// ── Customer contracts ─────────────────────────────────────────────────────

export interface CustomerAddressInput {
  zipcode:     string
  street:      string
  number:      string
  complement?: string
  district:    string
  city:        string
  state:       string
  country?:    string
  is_default?: boolean
}

export interface CustomerContactInput {
  type:       ContactType
  value:      string
  label?:     string
  is_primary?: boolean
}

export interface CreateCustomerRequest {
  person_type:  PersonType
  name:         string
  trade_name?:  string
  document?:    string
  email?:       string
  birth_date?:  string
  notes?:       string
  addresses?:   CustomerAddressInput[]
  contacts?:    CustomerContactInput[]
  tags?:        string[]
}

export interface UpdateCustomerRequest extends Partial<CreateCustomerRequest> {}

export interface CreateCustomerTagRequest {
  name:   string
  color?: string
}

// ── Catalog contracts ────────────────────────────────────────────────────────

export interface CreateBrandRequest {
  name:         string
  description?: string
  logo_url?:    string
  website_url?: string
  is_active?:   boolean
}

export interface UpdateBrandRequest extends Partial<CreateBrandRequest> {}

export interface CreateCategoryRequest {
  name:        string
  description?: string
  parent_id?:  string
  sort_order?: number
  is_active?:  boolean
}

export interface UpdateCategoryRequest extends Partial<CreateCategoryRequest> {}

export interface CreateAttributeGroupRequest {
  name:        string
  type:        AttributeType
  sort_order?: number
}

export interface CreateAttributeRequest {
  value:      string
  label:      string
  color_hex?: string
  sort_order?: number
}

export interface CreateGridRequest {
  name:                string
  attribute_group_id?: string
  description?:        string
  attribute_ids:       string[]
}

export interface UpdateProductRequest extends Partial<CreateProductRequest> {}

export interface CreateVariantRequest {
  product_id:       string
  sku:              string
  price_cents:      number
  name?:            string
  barcode?:         string
  cost_cents?:      number
  compare_at_cents?: number
  is_active?:       boolean
  is_default?:      boolean
  attribute_ids?:   string[]
}

export interface UpdateVariantRequest extends Partial<Omit<CreateVariantRequest, 'product_id'>> {}

export interface GenerateVariantsRequest {
  product_id:       string
  grid_id:          string
  sku_prefix:       string
  base_price_cents: number
}

// ── Inventory contracts ────────────────────────────────────────────────────

export interface CreateAdjustmentRequest {
  store_id:   string
  variant_id: string
  quantity:   number
  reason:     string
}

export interface CreateTransferRequest {
  origin_store_id:      string
  destination_store_id: string
  items: Array<{ variant_id: string; quantity: number }>
  notes?: string
}

export interface DispatchTransferRequest {
  items: Array<{ variant_id: string; quantity_sent: number }>
  notes?: string
}

export interface ReceiveTransferRequest {
  items: Array<{ variant_id: string; quantity_received: number }>
  notes?: string
}

// ── PDV / Sales contracts ────────────────────────────────────────────────────

export interface CreateCashRegisterRequest {
  store_id:   string
  code:       string
  name:       string
  is_active?: boolean
}

export interface UpdateCashRegisterRequest extends Partial<CreateCashRegisterRequest> {}

export interface OpenSessionRequest {
  cash_register_id?:    string
  opening_amount_cents: number
  notes?:               string
}

export interface CloseSessionRequest {
  closing_amount_cents: number
  notes?:               string
}

export interface UpdateFiscalSettingsRequest {
  policy_mode?:           FiscalPolicyMode
  min_sale_amount_cents?: number
  auto_issue_nfce?:       boolean
  allow_manual_nfce?:     boolean
  company_name?:          string
  cnpj?:                  string
}

// ── Sync contracts (PDV → API) ─────────────────────────────────────────────

export interface SyncSalePayload {
  local_uuid:    string
  store_uuid:    string
  user_uuid:     string
  customer_uuid: string | null
  subtotal:      number
  discount:      number
  total:         number
  notes:         string | null
  completed_at:  string
  items: Array<{
    product_uuid:        string
    variant_uuid:        string | null
    name_snapshot:       string
    sku_snapshot:        string
    attributes_snapshot: string
    cost_snapshot:       number | null
    quantity:            number
    unit_price:          number
    discount:            number
    total:               number
  }>
  payments: Array<{
    method:    string
    amount:    number
    reference: string | null
  }>
}
