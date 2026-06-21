import { apiClient } from '@/lib/api-client'
import type { Quote, Order, ApiSuccess, PaginationMeta } from '@store/shared-types'

// ── Interfaces ───────────────────────────────────────────────────────────────

export interface DocumentItemInput {
  product_variant_id?: string | null
  name_snapshot:       string
  sku_snapshot?:       string | null
  unit_of_measure?:    string
  quantity:            number
  unit_price_cents:    number
  discount_cents?:     number
  sort_order?:         number
  notes?:              string | null
}

export interface CreateQuoteRequest {
  store_id?:       string | null
  customer_id?:    string | null
  seller_pin?:     string | null
  validity_days?:  number
  discount_type?:  'fixed' | 'percent'
  discount_value?: number
  notes?:          string | null
  internal_notes?: string | null
  payment_terms?:  string | null
  items:           DocumentItemInput[]
}

export interface CreateOrderRequest {
  store_id?:       string | null
  customer_id?:    string | null
  seller_pin?:     string | null
  quote_id?:       string | null
  discount_type?:  'fixed' | 'percent'
  discount_value?: number
  notes?:          string | null
  internal_notes?: string | null
  payment_terms?:  string | null
  expected_at?:    string | null
  items:           DocumentItemInput[]
}

interface PaginatedList<T> {
  data: T[]
  meta: { total: number; per_page: number; current_page: number }
}

type PagedBody<T> = ApiSuccess<T[]> & { meta?: PaginationMeta }

function defaultMeta(): PaginationMeta {
  return { current_page: 1, per_page: 20, total: 0, last_page: 1 }
}

async function getList<T>(url: string): Promise<PaginatedList<T>> {
  const res  = await apiClient.get<PagedBody<T>>(url)
  const body = res.data
  if (!body.success) throw new Error((body as unknown as { message: string }).message)
  const m = (body.meta as unknown as PaginationMeta | undefined) ?? defaultMeta()
  return {
    data: body.data,
    meta: { total: m.total, per_page: m.per_page, current_page: m.current_page },
  }
}

async function getSingle<T>(url: string): Promise<T> {
  const res  = await apiClient.get<ApiSuccess<T>>(url)
  const body = res.data
  if (!body.success) throw new Error((body as unknown as { message: string }).message)
  return body.data
}

async function postSingle<T, D = unknown>(url: string, data?: D): Promise<T> {
  const res  = await apiClient.post<ApiSuccess<T>>(url, data)
  const body = res.data
  if (!body.success) throw new Error((body as unknown as { message: string }).message)
  return body.data
}

async function patchSingle<T, D = unknown>(url: string, data?: D): Promise<T> {
  const res  = await apiClient.patch<ApiSuccess<T>>(url, data)
  const body = res.data
  if (!body.success) throw new Error((body as unknown as { message: string }).message)
  return body.data
}

// ── Quotes ───────────────────────────────────────────────────────────────────

export const quotesService = {
  list: (params?: Record<string, string | number>) => {
    const qs = params ? '?' + new URLSearchParams(
      Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))
    ).toString() : ''
    return getList<Quote>(`/quotes${qs}`)
  },

  get:          (uuid: string)   => getSingle<Quote>(`/quotes/${uuid}`),
  getByNumber:  (number: string) => getSingle<Quote>(`/quotes/by-number/${encodeURIComponent(number)}`),
  create:            (payload: CreateQuoteRequest) => postSingle<Quote, CreateQuoteRequest>('/quotes', payload),
  resolveSellerPin:  (pin: string) => postSingle<{ uuid: string; name: string }>('/quotes/resolve-seller-pin', { pin }),
  markSent:          (uuid: string) => patchSingle<Quote>(`/quotes/${uuid}/mark-sent`),

  convert: (uuid: string, expectedAt?: string) =>
    postSingle<Order>(`/quotes/${uuid}/convert`, { expected_at: expectedAt }),

  delete: async (uuid: string) => {
    await apiClient.delete(`/quotes/${uuid}`)
  },
}

// ── Orders ───────────────────────────────────────────────────────────────────

export const ordersService = {
  list: (params?: Record<string, string | number>) => {
    const qs = params ? '?' + new URLSearchParams(
      Object.fromEntries(Object.entries(params).map(([k, v]) => [k, String(v)]))
    ).toString() : ''
    return getList<Order>(`/orders${qs}`)
  },

  get:         (uuid: string)   => getSingle<Order>(`/orders/${uuid}`),
  getByNumber: (number: string) => getSingle<Order>(`/orders/by-number/${encodeURIComponent(number)}`),
  create:      (payload: CreateOrderRequest) => postSingle<Order, CreateOrderRequest>('/orders', payload),

  updateStatus: (uuid: string, status: string, cancellationReason?: string) =>
    patchSingle<Order>(`/orders/${uuid}/status`, { status, cancellation_reason: cancellationReason }),

  delete: async (uuid: string) => {
    await apiClient.delete(`/orders/${uuid}`)
  },
}
