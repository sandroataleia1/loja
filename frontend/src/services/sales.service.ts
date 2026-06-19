import { apiClient, apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type {
  CashRegister, CashSession, CashMovement,
  Sale, FiscalDocument, FiscalSettings, TaxProfile,
  PaginatedResponse, PaginationMeta,
} from '@store/shared-types'
import type { ApiSuccess } from '@store/shared-types'
import type {
  CreateCashRegisterRequest, UpdateCashRegisterRequest,
  OpenSessionRequest, CloseSessionRequest,
  UpdateFiscalSettingsRequest,
} from '@store/contracts'

// ── Create Sale Payload ───────────────────────────────────────────────────────

export interface CreateSalePayload {
  session_id:           string
  customer_id:          string | null
  discount_total_cents?: number
  notes?:               string | null
  items: Array<{
    product_uuid:          string
    variant_uuid?:         string | null
    product_name:          string
    product_sku:           string
    quantity:              number
    unit_price_cents:      number
    discount_amount_cents?: number
  }>
  payments: Array<{
    method:        string
    amount_cents:  number
    installments?: number
    reference?:    string
    metadata?:     Record<string, unknown>
  }>
}

// ── Filters ───────────────────────────────────────────────────────────────────

export interface SessionFilters {
  store_id?: string; status?: string; per_page?: number; page?: number
}
export interface SaleFilters {
  store_id?: string; status?: string; session_id?: string
  customer_id?: string; per_page?: number; page?: number
}

// ── Service ───────────────────────────────────────────────────────────────────

export const salesService = {

  // ── Cash Registers ──────────────────────────────────────────────────────────

  getCashRegisters(): Promise<CashRegister[]> {
    return apiGet<CashRegister[]>('/sales/registers')
  },

  getCashRegister(uuid: string): Promise<CashRegister> {
    return apiGet<CashRegister>(`/sales/registers/${uuid}`)
  },

  createCashRegister(data: CreateCashRegisterRequest): Promise<CashRegister> {
    return apiPost<CashRegister, CreateCashRegisterRequest>('/sales/registers', data)
  },

  updateCashRegister(uuid: string, data: UpdateCashRegisterRequest): Promise<CashRegister> {
    return apiPut<CashRegister, UpdateCashRegisterRequest>(`/sales/registers/${uuid}`, data)
  },

  deleteCashRegister(uuid: string): Promise<void> {
    return apiDelete<void>(`/sales/registers/${uuid}`)
  },

  // ── Sessions ────────────────────────────────────────────────────────────────

  async getSessions(filters?: SessionFilters): Promise<PaginatedResponse<CashSession>> {
    const params = new URLSearchParams()
    if (filters?.store_id)  params.set('store_id', filters.store_id)
    if (filters?.status)    params.set('status', filters.status)
    if (filters?.per_page)  params.set('per_page', String(filters.per_page))
    if (filters?.page)      params.set('page', String(filters.page))
    const qs  = params.toString()
    const res = await apiClient.get<ApiSuccess<CashSession[]> & { meta?: PaginationMeta }>(
      `/sales/sessions${qs ? `?${qs}` : ''}`
    )
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as PaginationMeta) ?? { current_page: 1, per_page: 20, total: 0, last_page: 1 },
    }
  },

  getSession(uuid: string): Promise<CashSession> {
    return apiGet<CashSession>(`/sales/sessions/${uuid}`)
  },

  openSession(data: OpenSessionRequest): Promise<CashSession> {
    return apiPost<CashSession, OpenSessionRequest>('/sales/sessions/open', data)
  },

  closeSession(uuid: string, data: CloseSessionRequest): Promise<CashSession> {
    return apiPost<CashSession, CloseSessionRequest>(`/sales/sessions/${uuid}/close`, data)
  },

  getMovements(sessionUuid: string): Promise<CashMovement[]> {
    return apiGet<CashMovement[]>(`/sales/sessions/${sessionUuid}/movements`)
  },

  getSessionSummary(sessionUuid: string): Promise<{
    session_uuid:     string
    sale_count:       number
    total_sales:      number
    by_method: {
      cash:         number
      pix:          number
      credit_card:  number
      debit_card:   number
      store_credit: number
    }
    supply_total:     number
    withdrawal_total: number
    expected_balance: number
    opening_balance:  number
  }> {
    return apiGet(`/sales/sessions/${sessionUuid}/summary`)
  },

  recordWithdrawal(sessionUuid: string, data: { amount_cents: number; description: string }): Promise<void> {
    return apiPost<void, typeof data>(`/sales/sessions/${sessionUuid}/withdrawal`, data)
  },

  recordSupply(sessionUuid: string, data: { amount_cents: number; description: string }): Promise<void> {
    return apiPost<void, typeof data>(`/sales/sessions/${sessionUuid}/supply`, data)
  },

  // ── Sales ────────────────────────────────────────────────────────────────────

  async getSales(filters?: SaleFilters): Promise<PaginatedResponse<Sale>> {
    const params = new URLSearchParams()
    if (filters?.store_id)    params.set('store_id', filters.store_id)
    if (filters?.status)      params.set('status', filters.status)
    if (filters?.session_id)  params.set('session_id', filters.session_id)
    if (filters?.customer_id) params.set('customer_id', filters.customer_id)
    if (filters?.per_page)    params.set('per_page', String(filters.per_page))
    if (filters?.page)        params.set('page', String(filters.page))
    const qs  = params.toString()
    const res = await apiClient.get<ApiSuccess<Sale[]> & { meta?: PaginationMeta }>(
      `/sales${qs ? `?${qs}` : ''}`
    )
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as PaginationMeta) ?? { current_page: 1, per_page: 20, total: 0, last_page: 1 },
    }
  },

  getSale(uuid: string): Promise<Sale> {
    return apiGet<Sale>(`/sales/${uuid}`)
  },

  cancelSale(uuid: string, data?: { reason?: string }): Promise<Sale> {
    return apiPost<Sale, { reason?: string }>(`/sales/${uuid}/cancel`, data ?? {})
  },

  createSale(data: CreateSalePayload): Promise<Sale> {
    return apiPost<Sale, CreateSalePayload>('/sales', data)
  },

  completeSale(uuid: string): Promise<Sale> {
    return apiPost<Sale, Record<string, never>>(`/sales/${uuid}/complete`, {})
  },

  // ── Fiscal ───────────────────────────────────────────────────────────────────

  getFiscalSettings(): Promise<FiscalSettings> {
    return apiGet<FiscalSettings>('/fiscal/settings')
  },

  updateFiscalSettings(data: UpdateFiscalSettingsRequest): Promise<FiscalSettings> {
    return apiPut<FiscalSettings, UpdateFiscalSettingsRequest>('/fiscal/settings', data)
  },

  async getFiscalDocuments(filters?: { status?: string; per_page?: number }): Promise<PaginatedResponse<FiscalDocument>> {
    const params = new URLSearchParams()
    if (filters?.status)   params.set('status', filters.status)
    if (filters?.per_page) params.set('per_page', String(filters.per_page))
    const qs  = params.toString()
    const res = await apiClient.get<ApiSuccess<FiscalDocument[]> & { meta?: PaginationMeta }>(
      `/fiscal/documents${qs ? `?${qs}` : ''}`
    )
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as PaginationMeta) ?? { current_page: 1, per_page: 20, total: 0, last_page: 1 },
    }
  },

  cancelFiscalDocument(uuid: string, reason?: string): Promise<FiscalDocument> {
    return apiPost<FiscalDocument, { reason?: string }>(`/fiscal/documents/${uuid}/cancel`, { reason })
  },

  retryFiscalDocument(uuid: string): Promise<FiscalDocument> {
    return apiPost<FiscalDocument>(`/fiscal/documents/${uuid}/retry`)
  },

  getTaxProfiles(): Promise<TaxProfile[]> {
    return apiGet<TaxProfile[]>('/fiscal/tax-profiles')
  },
}
