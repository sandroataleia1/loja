import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type { PaginatedResponse } from '@/types'

export type PartnerType = 'MASON' | 'FOREMAN' | 'ARCHITECT' | 'ENGINEER' | 'DESIGNER' | 'OTHER'

export const PARTNER_TYPE_LABELS: Record<PartnerType, string> = {
  MASON:     'Pedreiro',
  FOREMAN:   'Mestre de Obras',
  ARCHITECT: 'Arquiteto',
  ENGINEER:  'Engenheiro',
  DESIGNER:  'Designer de Interiores',
  OTHER:     'Outro',
}

export interface PartnerContact {
  uuid:        string
  type:        'PHONE' | 'WHATSAPP' | 'EMAIL' | 'OTHER'
  value:       string
  label?:      string
  is_primary:  boolean
}

export interface Partner {
  uuid:        string
  name:        string
  type:        PartnerType
  type_label:  string
  document?:   string
  email?:      string
  phone?:      string
  notes?:      string
  is_active:   boolean
  contacts?:   PartnerContact[]
  created_at:  string
  updated_at:  string
}

export interface PartnerFilters {
  q?:        string
  type?:     PartnerType
  active?:   boolean
  per_page?: number
  page?:     number
}

export interface CreatePartnerPayload {
  name:       string
  type:       PartnerType
  document?:  string
  email?:     string
  phone?:     string
  notes?:     string
  is_active?: boolean
}

export interface PartnerContactPayload {
  type:        'PHONE' | 'WHATSAPP' | 'EMAIL' | 'OTHER'
  value:       string
  label?:      string
  is_primary?: boolean
}

export const partnersService = {
  getPartners(filters?: PartnerFilters): Promise<PaginatedResponse<Partner>> {
    const p = new URLSearchParams()
    if (filters?.q)        p.set('q', filters.q)
    if (filters?.type)     p.set('type', filters.type)
    if (filters?.active)   p.set('active', '1')
    if (filters?.per_page) p.set('per_page', String(filters.per_page))
    if (filters?.page)     p.set('page', String(filters.page))
    const qs = p.toString()
    return apiGet<PaginatedResponse<Partner>>(`/partner-professionals${qs ? `?${qs}` : ''}`)
  },

  getPartner(uuid: string): Promise<Partner> {
    return apiGet<Partner>(`/partner-professionals/${uuid}`)
  },

  createPartner(data: CreatePartnerPayload): Promise<Partner> {
    return apiPost<Partner, CreatePartnerPayload>('/partner-professionals', data)
  },

  updatePartner(uuid: string, data: Partial<CreatePartnerPayload>): Promise<Partner> {
    return apiPut<Partner, Partial<CreatePartnerPayload>>(`/partner-professionals/${uuid}`, data)
  },

  deletePartner(uuid: string): Promise<void> {
    return apiDelete(`/partner-professionals/${uuid}`)
  },

  createPartnerContact(partnerUuid: string, data: PartnerContactPayload): Promise<PartnerContact> {
    return apiPost(`/partner-professionals/${partnerUuid}/contacts`, data)
  },

  deletePartnerContact(partnerUuid: string, contactUuid: string): Promise<void> {
    return apiDelete(`/partner-professionals/${partnerUuid}/contacts/${contactUuid}`)
  },
}
