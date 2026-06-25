import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type { PaginatedResponse } from '@/types'

export interface Carrier {
  uuid:        string
  code?:       string
  name:        string
  trade_name?: string
  cnpj?:       string
  ie?:         string
  email?:      string
  phone?:      string
  notes?:      string
  is_active:   boolean
  addresses?:  CarrierAddress[]
  contacts?:   CarrierContact[]
  created_at:  string
  updated_at:  string
}

export interface CarrierAddress {
  uuid:        string
  zipcode:     string
  street:      string
  number:      string
  complement?: string
  district:    string
  city:        string
  state:       string
  country:     string
  is_default:  boolean
}

export interface CarrierContact {
  uuid:        string
  type:        'PHONE' | 'WHATSAPP' | 'EMAIL' | 'OTHER'
  value:       string
  label?:      string
  is_primary:  boolean
}

export interface CarrierFilters {
  q?:        string
  active?:   boolean
  per_page?: number
  page?:     number
}

export interface CreateCarrierPayload {
  name:        string
  trade_name?: string
  cnpj?:       string
  ie?:         string
  email?:      string
  phone?:      string
  notes?:      string
  code?:       string
  is_active?:  boolean
}

export interface CarrierAddressPayload {
  zipcode:     string
  street:      string
  number:      string
  complement?: string
  district:    string
  city:        string
  state:       string
  is_default?: boolean
}

export interface CarrierContactPayload {
  type:        'PHONE' | 'WHATSAPP' | 'EMAIL' | 'OTHER'
  value:       string
  label?:      string
  is_primary?: boolean
}

export const carriersService = {
  getCarriers(filters?: CarrierFilters): Promise<PaginatedResponse<Carrier>> {
    const p = new URLSearchParams()
    if (filters?.q)        p.set('q', filters.q)
    if (filters?.active)   p.set('active', '1')
    if (filters?.per_page) p.set('per_page', String(filters.per_page))
    if (filters?.page)     p.set('page', String(filters.page))
    const qs = p.toString()
    return apiGet<PaginatedResponse<Carrier>>(`/carriers${qs ? `?${qs}` : ''}`)
  },

  getCarrier(uuid: string): Promise<Carrier> {
    return apiGet<Carrier>(`/carriers/${uuid}`)
  },

  createCarrier(data: CreateCarrierPayload): Promise<Carrier> {
    return apiPost<Carrier, CreateCarrierPayload>('/carriers', data)
  },

  updateCarrier(uuid: string, data: Partial<CreateCarrierPayload>): Promise<Carrier> {
    return apiPut<Carrier, Partial<CreateCarrierPayload>>(`/carriers/${uuid}`, data)
  },

  deleteCarrier(uuid: string): Promise<void> {
    return apiDelete(`/carriers/${uuid}`)
  },

  createCarrierAddress(carrierUuid: string, data: CarrierAddressPayload): Promise<CarrierAddress> {
    return apiPost(`/carriers/${carrierUuid}/addresses`, data)
  },
  updateCarrierAddress(carrierUuid: string, addressUuid: string, data: Partial<CarrierAddressPayload>): Promise<CarrierAddress> {
    return apiPut(`/carriers/${carrierUuid}/addresses/${addressUuid}`, data)
  },
  deleteCarrierAddress(carrierUuid: string, addressUuid: string): Promise<void> {
    return apiDelete(`/carriers/${carrierUuid}/addresses/${addressUuid}`)
  },

  createCarrierContact(carrierUuid: string, data: CarrierContactPayload): Promise<CarrierContact> {
    return apiPost(`/carriers/${carrierUuid}/contacts`, data)
  },
  updateCarrierContact(carrierUuid: string, contactUuid: string, data: Partial<CarrierContactPayload>): Promise<CarrierContact> {
    return apiPut(`/carriers/${carrierUuid}/contacts/${contactUuid}`, data)
  },
  deleteCarrierContact(carrierUuid: string, contactUuid: string): Promise<void> {
    return apiDelete(`/carriers/${carrierUuid}/contacts/${contactUuid}`)
  },
}
