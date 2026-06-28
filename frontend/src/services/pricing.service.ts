import { apiClient, apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type {
  PriceList,
  ProductPrice,
  PriceHistoryEntry,
  PaginatedResponse,
  PaginationMeta,
  ApiSuccess,
} from '@store/shared-types'
import type { CreatePriceListRequest, UpdatePriceListRequest, UpsertPricesRequest } from '@store/contracts'

// ── Filters ──────────────────────────────────────────────────────────────────

export interface PriceListFilters {
  type?:             string
  include_inactive?: boolean
  include_expired?:  boolean
}

export interface PriceFilters {
  product_id?: string
  variant_id?: string
  search?:     string
  per_page?:   number
  page?:       number
}

// ── Service ───────────────────────────────────────────────────────────────────

export const pricingService = {

  // ── Price Lists ───────────────────────────────────────────────────────────

  getPriceLists(filters?: PriceListFilters): Promise<PriceList[]> {
    const params = new URLSearchParams()
    if (filters?.type)             params.set('type', filters.type)
    if (filters?.include_inactive) params.set('include_inactive', 'true')
    if (filters?.include_expired)  params.set('include_expired', 'true')
    const qs = params.toString()
    return apiGet<PriceList[]>(`/catalog/price-lists${qs ? '?' + qs : ''}`)
  },

  getPriceList(uuid: string): Promise<PriceList> {
    return apiGet<PriceList>(`/catalog/price-lists/${uuid}`)
  },

  createPriceList(data: CreatePriceListRequest): Promise<PriceList> {
    return apiPost<PriceList, CreatePriceListRequest>('/catalog/price-lists', data)
  },

  updatePriceList(uuid: string, data: UpdatePriceListRequest): Promise<PriceList> {
    return apiPut<PriceList, UpdatePriceListRequest>(`/catalog/price-lists/${uuid}`, data)
  },

  deletePriceList(uuid: string): Promise<void> {
    return apiDelete<void>(`/catalog/price-lists/${uuid}`)
  },

  // ── Prices ────────────────────────────────────────────────────────────────

  async getPrices(priceListUuid: string, filters?: PriceFilters): Promise<PaginatedResponse<ProductPrice>> {
    const params = new URLSearchParams()
    if (filters?.product_id) params.set('product_id', filters.product_id)
    if (filters?.variant_id) params.set('variant_id', filters.variant_id)
    if (filters?.search)     params.set('search', filters.search)
    if (filters?.per_page)   params.set('per_page', String(filters.per_page))
    if (filters?.page)       params.set('page', String(filters.page))
    const qs  = params.toString()
    const url = `/catalog/price-lists/${priceListUuid}/prices${qs ? '?' + qs : ''}`
    const res  = await apiClient.get<ApiSuccess<ProductPrice[]> & { meta?: PaginationMeta }>(url)
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as unknown as PaginationMeta) ?? {
        current_page: 1,
        per_page:     50,
        total:        0,
        last_page:    1,
      },
    }
  },

  upsertPrices(priceListUuid: string, data: UpsertPricesRequest): Promise<{ message: string }> {
    return apiPost<{ message: string }, UpsertPricesRequest>(
      `/catalog/price-lists/${priceListUuid}/prices`,
      data,
    )
  },

  // ── History ───────────────────────────────────────────────────────────────

  getHistory(priceListUuid: string): Promise<PriceHistoryEntry[]> {
    return apiGet<PriceHistoryEntry[]>(`/catalog/price-lists/${priceListUuid}/history`)
  },

  // ── Import ────────────────────────────────────────────────────────────────

  importCSV(priceListUuid: string, file: File): Promise<{ message: string }> {
    const form = new FormData()
    form.append('file', file)
    return apiPost<{ message: string }>(`/catalog/price-lists/${priceListUuid}/import`, form)
  },

  getImportTemplate(): string {
    return `${process.env.NEXT_PUBLIC_API_URL}/catalog/price-lists/import/template`
  },

  // ── Resolve ───────────────────────────────────────────────────────────────

  resolve(
    variantId: string,
    customerId?: string,
  ): Promise<{ price_cents: number; price_list_id: string; formatted: string }> {
    const params = new URLSearchParams({ variant_id: variantId })
    if (customerId) params.set('customer_id', customerId)
    return apiGet(`/pricing/resolve?${params.toString()}`)
  },

  // ── Apply discount ────────────────────────────────────────────────────────

  applyDiscount(data: {
    discount_percent: number
    price_cents:      number
    price_list_id?:   string
  }): Promise<{
    original_price_cents: number
    discount_percent:     number
    discount_cents:       number
    final_price_cents:    number
    allowed:              boolean
    max_allowed_percent:  number
  }> {
    return apiPost('/pricing/apply-discount', data)
  },
}
