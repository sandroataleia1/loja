import { apiGet, apiPut } from '@/lib/api-client'

// ── Types ─────────────────────────────────────────────────────────────────────

export interface CommercialSettings {
  require_salesperson:        boolean
  require_quote_before_order: boolean
  allow_order_without_stock:  boolean
  allow_free_discount:        boolean
  default_discount_limit:     number
  require_discount_approval:  boolean
  require_price_approval:     boolean
  use_price_table:            boolean
  quote_validity_days:        number
}

export interface FinancialSettings {
  default_interest_rate:      number
  default_fine_rate:          number
  default_discount_rate:      number
  tolerance_days:             number
  rounding_mode:              'half_up' | 'half_down' | 'ceil' | 'floor'
  auto_generate_bills:        boolean
  auto_apply_customer_credit: boolean
}

export interface CreditSettings {
  default_credit_limit:       number
  block_sale_without_credit:  boolean
  allow_exceed_limit:         boolean
  require_approval_to_exceed: boolean
}

export interface InventorySettings {
  allow_negative_stock:  boolean
  auto_reserve:          boolean
  auto_deduct:           boolean
  require_picking:       boolean
  require_shipping:      boolean
  require_counting:      boolean
  auto_update_cost:      boolean
}

export interface BillingSettings {
  billing_mode: 'by_order' | 'by_invoice' | 'future'
}

export interface CommissionSettings {
  commission_on_sale:      boolean
  commission_on_payment:   boolean
  proportional_commission: boolean
  commission_by_margin:    boolean
}

export interface LogisticsSettings {
  require_picking:          boolean
  require_shipping:         boolean
  require_packing_list:     boolean
  require_delivery:         boolean
  require_delivery_receipt: boolean
}

export interface SystemSettings {
  commercial: CommercialSettings
  financial:  FinancialSettings
  credit:     CreditSettings
  inventory:  InventorySettings
  billing:    BillingSettings
  commission: CommissionSettings
  logistics:  LogisticsSettings
}

export type SettingsSection = keyof SystemSettings

export interface FeatureFlag {
  feature:     string
  label:       string
  description: string
  is_enabled:  boolean
}

const BASE = '/system-settings'

export const systemSettingsService = {
  getAll(): Promise<SystemSettings> {
    return apiGet<SystemSettings>(BASE)
  },

  updateSection<S extends SettingsSection>(
    section: S,
    data: Partial<SystemSettings[S]>,
  ): Promise<SystemSettings[S]> {
    return apiPut<SystemSettings[S], Partial<SystemSettings[S]>>(`${BASE}/${section}`, data)
  },

  getFeatures(): Promise<FeatureFlag[]> {
    return apiGet<FeatureFlag[]>(`${BASE}/features`)
  },

  updateFeatures(features: Record<string, boolean>): Promise<void> {
    return apiPut<void, { features: Record<string, boolean> }>(`${BASE}/features`, { features })
  },
}
