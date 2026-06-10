import { apiClient, apiGet, apiPost } from '@/lib/api-client'
import type { Conditional, PaginatedResponse, PaginationMeta } from '@store/shared-types'
import type { ApiSuccess } from '@store/shared-types'
import type {
  CreateConditionalRequest,
  ReturnConditionalRequest,
  ConvertConditionalRequest,
} from '@store/contracts'

// ── Filters ───────────────────────────────────────────────────────────────────

export interface ConditionalFilters {
  status?:      string
  store_id?:    string
  customer_id?: string
  q?:           string
  overdue?:     boolean
  per_page?:    number
  page?:        number
}

// ── Service ───────────────────────────────────────────────────────────────────

export const conditionalService = {

  async getConditionals(filters?: ConditionalFilters): Promise<PaginatedResponse<Conditional>> {
    const params = new URLSearchParams()
    if (filters?.status)      params.set('status', filters.status)
    if (filters?.store_id)    params.set('store_id', filters.store_id)
    if (filters?.customer_id) params.set('customer_id', filters.customer_id)
    if (filters?.q)           params.set('q', filters.q)
    if (filters?.overdue !== undefined) params.set('overdue', filters.overdue ? '1' : '0')
    if (filters?.per_page)    params.set('per_page', String(filters.per_page))
    if (filters?.page)        params.set('page', String(filters.page))
    const qs  = params.toString()
    const url = `/conditionals${qs ? `?${qs}` : ''}`

    const res  = await apiClient.get<ApiSuccess<Conditional[]> & { meta?: PaginationMeta }>(url)
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as unknown as PaginationMeta) ?? {
        current_page: 1,
        per_page:     20,
        total:        0,
        last_page:    1,
      },
    }
  },

  getConditional(uuid: string): Promise<Conditional> {
    return apiGet<Conditional>(`/conditionals/${uuid}`)
  },

  createConditional(data: CreateConditionalRequest): Promise<Conditional> {
    return apiPost<Conditional, CreateConditionalRequest>('/conditionals', data)
  },

  returnItems(uuid: string, data: ReturnConditionalRequest): Promise<Conditional> {
    return apiPost<Conditional, ReturnConditionalRequest>(`/conditionals/${uuid}/return`, data)
  },

  convertItems(uuid: string, data: ConvertConditionalRequest): Promise<Conditional> {
    return apiPost<Conditional, ConvertConditionalRequest>(`/conditionals/${uuid}/convert`, data)
  },

  cancelConditional(uuid: string): Promise<Conditional> {
    return apiPost<Conditional>(`/conditionals/${uuid}/cancel`)
  },
}
