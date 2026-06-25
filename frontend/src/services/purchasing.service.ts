import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type { Supplier, PurchaseOrder } from '@/types/shared-types'
import type { PaginatedResponse } from '@/types'

export interface SupplierFilters {
  q?:        string
  active?:   boolean
  per_page?: number
  page?:     number
}

export interface PurchaseOrderFilters {
  status?:      string
  supplier_id?: string
  q?:           string
  per_page?:    number
  page?:        number
}

export interface SupplierAddressPayload {
  zipcode:     string
  street:      string
  number:      string
  complement?: string
  district:    string
  city:        string
  state:       string
  is_default?: boolean
}

export interface SupplierContactPayload {
  type:        'PHONE' | 'WHATSAPP' | 'EMAIL' | 'OTHER'
  value:       string
  label?:      string
  is_primary?: boolean
}

export interface CreateSupplierPayload {
  person_type:  'INDIVIDUAL' | 'COMPANY'
  name:         string
  trade_name?:  string
  document?:    string
  ie?:          string
  im?:          string
  email?:       string
  phone?:       string
  notes?:       string
}

export interface CreatePurchaseOrderPayload {
  supplier_id:             string
  store_id:                string
  order_date:              string
  expected_delivery_date?: string
  discount?:               number
  notes?:                  string
  items: Array<{
    product_variant_id: string
    quantity:           number
    unit_cost:          number
  }>
}

export interface ReceivePayload {
  items: Array<{
    order_item_uuid:    string
    quantity_received:  number
  }>
  notes?: string
}

export const purchasingService = {
  // ── Suppliers ─────────────────────────────────────────────────────────────

  getSuppliers(filters?: SupplierFilters): Promise<PaginatedResponse<Supplier>> {
    const p = new URLSearchParams()
    if (filters?.q)        p.set('q', filters.q)
    if (filters?.active)   p.set('active', '1')
    if (filters?.per_page) p.set('per_page', String(filters.per_page))
    if (filters?.page)     p.set('page', String(filters.page))
    const qs = p.toString()
    return apiGet<PaginatedResponse<Supplier>>(`/purchasing/suppliers${qs ? `?${qs}` : ''}`)
  },

  getSupplier(uuid: string): Promise<Supplier> {
    return apiGet<Supplier>(`/purchasing/suppliers/${uuid}`)
  },

  createSupplier(data: CreateSupplierPayload): Promise<Supplier> {
    return apiPost<Supplier, CreateSupplierPayload>('/purchasing/suppliers', data)
  },

  updateSupplier(uuid: string, data: Partial<CreateSupplierPayload> & { is_active?: boolean }): Promise<Supplier> {
    return apiPut<Supplier, typeof data>(`/purchasing/suppliers/${uuid}`, data)
  },

  deleteSupplier(uuid: string): Promise<void> {
    return apiDelete(`/purchasing/suppliers/${uuid}`)
  },

  getSupplierAddresses(uuid: string): Promise<SupplierAddressPayload[]> {
    return apiGet(`/purchasing/suppliers/${uuid}/addresses`)
  },
  createSupplierAddress(uuid: string, data: SupplierAddressPayload): Promise<SupplierAddressPayload> {
    return apiPost(`/purchasing/suppliers/${uuid}/addresses`, data)
  },
  updateSupplierAddress(supplierUuid: string, addressUuid: string, data: Partial<SupplierAddressPayload>): Promise<SupplierAddressPayload> {
    return apiPut(`/purchasing/suppliers/${supplierUuid}/addresses/${addressUuid}`, data)
  },
  deleteSupplierAddress(supplierUuid: string, addressUuid: string): Promise<void> {
    return apiDelete(`/purchasing/suppliers/${supplierUuid}/addresses/${addressUuid}`)
  },

  getSupplierContacts(uuid: string): Promise<SupplierContactPayload[]> {
    return apiGet(`/purchasing/suppliers/${uuid}/contacts`)
  },
  createSupplierContact(uuid: string, data: SupplierContactPayload): Promise<SupplierContactPayload> {
    return apiPost(`/purchasing/suppliers/${uuid}/contacts`, data)
  },
  updateSupplierContact(supplierUuid: string, contactUuid: string, data: Partial<SupplierContactPayload>): Promise<SupplierContactPayload> {
    return apiPut(`/purchasing/suppliers/${supplierUuid}/contacts/${contactUuid}`, data)
  },
  deleteSupplierContact(supplierUuid: string, contactUuid: string): Promise<void> {
    return apiDelete(`/purchasing/suppliers/${supplierUuid}/contacts/${contactUuid}`)
  },

  // ── Purchase Orders ───────────────────────────────────────────────────────

  getPurchaseOrders(filters?: PurchaseOrderFilters): Promise<PaginatedResponse<PurchaseOrder>> {
    const p = new URLSearchParams()
    if (filters?.status)      p.set('status', filters.status)
    if (filters?.supplier_id) p.set('supplier_id', filters.supplier_id)
    if (filters?.q)           p.set('q', filters.q)
    if (filters?.per_page)    p.set('per_page', String(filters.per_page))
    if (filters?.page)        p.set('page', String(filters.page))
    const qs = p.toString()
    return apiGet<PaginatedResponse<PurchaseOrder>>(`/purchasing/orders${qs ? `?${qs}` : ''}`)
  },

  getPurchaseOrder(uuid: string): Promise<PurchaseOrder> {
    return apiGet<PurchaseOrder>(`/purchasing/orders/${uuid}`)
  },

  createPurchaseOrder(data: CreatePurchaseOrderPayload): Promise<PurchaseOrder> {
    return apiPost<PurchaseOrder, CreatePurchaseOrderPayload>('/purchasing/orders', data)
  },

  sendPurchaseOrder(uuid: string): Promise<PurchaseOrder> {
    return apiPost<PurchaseOrder>(`/purchasing/orders/${uuid}/send`)
  },

  cancelPurchaseOrder(uuid: string): Promise<PurchaseOrder> {
    return apiPost<PurchaseOrder>(`/purchasing/orders/${uuid}/cancel`)
  },

  receivePurchaseOrder(uuid: string, data: ReceivePayload): Promise<PurchaseOrder> {
    return apiPost<PurchaseOrder, ReceivePayload>(`/purchasing/orders/${uuid}/receive`, data)
  },
}
