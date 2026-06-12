// ── Enums ─────────────────────────────────────────────────────────────────────

export type FinancialEntryStatus       = 'pending' | 'paid' | 'overdue' | 'cancelled' | 'partially_paid'
export type FinancialEntryType         = 'income' | 'expense'
export type FinancialAccountType       = 'cash' | 'bank' | 'digital_wallet'
export type FinancialInstallmentStatus = 'pending' | 'paid' | 'overdue' | 'cancelled'
export type ReconciliationStatus       = 'pending' | 'reconciled' | 'divergent'

// ── Resources ─────────────────────────────────────────────────────────────────

export interface FinancialAccount {
  uuid:                  string
  code:                  string
  name:                  string
  type:                  FinancialAccountType
  type_label:            string
  is_active:             boolean
  current_balance_cents: number
  store_id:              string | null
  created_at:            string | null
  updated_at:            string | null
}

export interface FinancialCategory {
  uuid:       string
  name:       string
  type:       FinancialEntryType
  type_label: string
  is_active:  boolean
  created_at: string | null
  updated_at: string | null
}

export interface FinancialInstallment {
  uuid:               string
  installment_number: number
  due_date:           string | null
  amount_cents:       number
  status:             FinancialInstallmentStatus
  status_label:       string
  paid_at:            string | null
}

export interface FinancialEntry {
  uuid:                   string
  store_id:               string | null
  type:                   FinancialEntryType
  type_label:             string
  status:                 FinancialEntryStatus
  status_label:           string
  amount_cents:           number
  due_date:               string | null
  paid_at:                string | null
  description:            string | null
  reference_type:         string | null
  reference_id:           string | null
  external_reference:     string | null
  reconciliation_status:  ReconciliationStatus | null
  financial_account_id:   string | null
  category_id:            string | null
  account:                FinancialAccount | null
  category:               FinancialCategory | null
  installments:           FinancialInstallment[]
  created_at:             string | null
  updated_at:             string | null
}

export interface FinancialTransfer {
  uuid:                   string
  origin_account_id:      string
  destination_account_id: string
  amount_cents:           number
  transferred_at:         string | null
  description:            string | null
  origin_account:         FinancialAccount | null
  destination_account:    FinancialAccount | null
  created_at:             string | null
}

// ── Request DTOs ──────────────────────────────────────────────────────────────

export interface CreateFinancialEntryRequest {
  type:                 FinancialEntryType
  category_id?:         string
  financial_account_id?: string
  amount_cents:         number
  due_date:             string
  description?:         string
  external_reference?:  string
  store_id?:            string
  installments?:        { due_date: string; amount_cents: number }[]
}

export interface PayFinancialEntryRequest {
  financial_account_id?: string
  amount_cents?:         number
  paid_at?:              string
  installment_number?:   number
}

export interface CreateFinancialAccountRequest {
  name:                   string
  type:                   FinancialAccountType
  code?:                  string
  is_active?:             boolean
  initial_balance_cents?: number
  store_id?:              string
}

export interface UpdateFinancialAccountRequest {
  name?:      string
  code?:      string
  is_active?: boolean
}

export interface CreateFinancialCategoryRequest {
  name: string
  type: FinancialEntryType
}

export interface UpdateFinancialCategoryRequest {
  name?:      string
  is_active?: boolean
}

export interface CreateFinancialTransferRequest {
  origin_account_id:      string
  destination_account_id: string
  amount_cents:           number
  transferred_at?:        string
  description?:           string
}

// ── Filters ───────────────────────────────────────────────────────────────────

export interface FinancialEntryFilters {
  type?:        FinancialEntryType
  status?:      FinancialEntryStatus
  category_id?: string
  account_id?:  string
  due_from?:    string
  due_to?:      string
  per_page?:    number
  page?:        number
}

export interface FinancialTransferFilters {
  per_page?: number
  page?:     number
}
