import { apiGet, apiPost, apiPut } from '@/lib/api-client'
import type { PaginatedResponse } from '@/types'

export interface Seller {
  uuid:              string
  user_id:           string
  code?:             string
  commission_rate?:  number
  is_active:         boolean
  notes?:            string
  user?: {
    uuid:  string
    name:  string
    email: string
  }
  created_at: string
  updated_at: string
}

export interface SellerFilters {
  q?:        string
  active?:   boolean
  per_page?: number
  page?:     number
}

export interface CreateSellerPayload {
  user_id:          string
  code?:            string
  commission_rate?: number
  is_active?:       boolean
  notes?:           string
}

export const sellersService = {
  getSellers(filters?: SellerFilters): Promise<PaginatedResponse<Seller>> {
    const p = new URLSearchParams()
    if (filters?.q)        p.set('q', filters.q)
    if (filters?.active)   p.set('active', '1')
    if (filters?.per_page) p.set('per_page', String(filters.per_page))
    if (filters?.page)     p.set('page', String(filters.page))
    const qs = p.toString()
    return apiGet<PaginatedResponse<Seller>>(`/sellers${qs ? `?${qs}` : ''}`)
  },

  getSeller(uuid: string): Promise<Seller> {
    return apiGet<Seller>(`/sellers/${uuid}`)
  },

  createSeller(data: CreateSellerPayload): Promise<Seller> {
    return apiPost<Seller, CreateSellerPayload>('/sellers', data)
  },

  updateSeller(uuid: string, data: Partial<CreateSellerPayload>): Promise<Seller> {
    return apiPut<Seller, Partial<CreateSellerPayload>>(`/sellers/${uuid}`, data)
  },
}
