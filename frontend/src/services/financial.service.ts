import { apiClient, apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type {
  FinancialEntry, FinancialAccount, FinancialCategory, FinancialTransfer,
  FinancialEntryFilters, FinancialTransferFilters,
  CreateFinancialEntryRequest, PayFinancialEntryRequest,
  CreateFinancialAccountRequest, UpdateFinancialAccountRequest,
  CreateFinancialCategoryRequest, UpdateFinancialCategoryRequest,
  CreateFinancialTransferRequest,
} from '@/types/financial'
import type { PaginatedResponse, PaginationMeta } from '@store/shared-types'
import type { ApiSuccess } from '@store/shared-types'

// ── Service ───────────────────────────────────────────────────────────────────

export const financialService = {

  // ── Entries ─────────────────────────────────────────────────────────────────

  async getEntries(filters?: FinancialEntryFilters): Promise<PaginatedResponse<FinancialEntry>> {
    const params = new URLSearchParams()
    if (filters?.type)        params.set('type', filters.type)
    if (filters?.status)      params.set('status', filters.status)
    if (filters?.category_id) params.set('category_id', filters.category_id)
    if (filters?.account_id)  params.set('account_id', filters.account_id)
    if (filters?.due_from)    params.set('due_from', filters.due_from)
    if (filters?.due_to)      params.set('due_to', filters.due_to)
    if (filters?.per_page)    params.set('per_page', String(filters.per_page))
    if (filters?.page)        params.set('page', String(filters.page))
    const qs  = params.toString()
    const res = await apiClient.get<ApiSuccess<FinancialEntry[]> & { meta?: PaginationMeta }>(
      `/finance/entries${qs ? `?${qs}` : ''}`
    )
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as PaginationMeta) ?? { current_page: 1, per_page: 20, total: 0, last_page: 1 },
    }
  },

  getEntry(uuid: string): Promise<FinancialEntry> {
    return apiGet<FinancialEntry>(`/finance/entries/${uuid}`)
  },

  createEntry(data: CreateFinancialEntryRequest): Promise<FinancialEntry> {
    return apiPost<FinancialEntry, CreateFinancialEntryRequest>('/finance/entries', data)
  },

  payEntry(uuid: string, data: PayFinancialEntryRequest): Promise<FinancialEntry> {
    return apiPost<FinancialEntry, PayFinancialEntryRequest>(`/finance/entries/${uuid}/pay`, data)
  },

  cancelEntry(uuid: string, reason?: string): Promise<FinancialEntry> {
    return apiPost<FinancialEntry, { reason?: string }>(`/finance/entries/${uuid}/cancel`, { reason })
  },

  // ── Accounts ────────────────────────────────────────────────────────────────

  getAccounts(): Promise<FinancialAccount[]> {
    return apiGet<FinancialAccount[]>('/finance/accounts')
  },

  getAccount(uuid: string): Promise<FinancialAccount> {
    return apiGet<FinancialAccount>(`/finance/accounts/${uuid}`)
  },

  createAccount(data: CreateFinancialAccountRequest): Promise<FinancialAccount> {
    return apiPost<FinancialAccount, CreateFinancialAccountRequest>('/finance/accounts', data)
  },

  updateAccount(uuid: string, data: UpdateFinancialAccountRequest): Promise<FinancialAccount> {
    return apiPut<FinancialAccount, UpdateFinancialAccountRequest>(`/finance/accounts/${uuid}`, data)
  },

  deleteAccount(uuid: string): Promise<void> {
    return apiDelete<void>(`/finance/accounts/${uuid}`)
  },

  // ── Categories ───────────────────────────────────────────────────────────────

  getCategories(): Promise<FinancialCategory[]> {
    return apiGet<FinancialCategory[]>('/finance/categories')
  },

  createCategory(data: CreateFinancialCategoryRequest): Promise<FinancialCategory> {
    return apiPost<FinancialCategory, CreateFinancialCategoryRequest>('/finance/categories', data)
  },

  updateCategory(uuid: string, data: UpdateFinancialCategoryRequest): Promise<FinancialCategory> {
    return apiPut<FinancialCategory, UpdateFinancialCategoryRequest>(`/finance/categories/${uuid}`, data)
  },

  deleteCategory(uuid: string): Promise<void> {
    return apiDelete<void>(`/finance/categories/${uuid}`)
  },

  // ── Transfers ────────────────────────────────────────────────────────────────

  async getTransfers(filters?: FinancialTransferFilters): Promise<PaginatedResponse<FinancialTransfer>> {
    const params = new URLSearchParams()
    if (filters?.per_page) params.set('per_page', String(filters.per_page))
    if (filters?.page)     params.set('page', String(filters.page))
    const qs  = params.toString()
    const res = await apiClient.get<ApiSuccess<FinancialTransfer[]> & { meta?: PaginationMeta }>(
      `/finance/transfers${qs ? `?${qs}` : ''}`
    )
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as PaginationMeta) ?? { current_page: 1, per_page: 20, total: 0, last_page: 1 },
    }
  },

  createTransfer(data: CreateFinancialTransferRequest): Promise<FinancialTransfer> {
    return apiPost<FinancialTransfer, CreateFinancialTransferRequest>('/finance/transfers', data)
  },
}
