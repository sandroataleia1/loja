import { apiClient, apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'
import type { Customer, CustomerAddress, CustomerContact, CustomerTag, PaginatedResponse, PaginationMeta } from '@store/shared-types'
import type { ApiSuccess } from '@store/shared-types'
import type { CreateCustomerRequest, UpdateCustomerRequest, CreateCustomerTagRequest } from '@store/contracts'

export interface CustomerFilters {
  q?:               string
  document?:        string
  email?:           string
  tag?:             string
  active?:          boolean
  include_default?: boolean
  per_page?:        number
  page?:            number
  status?:          'active' | 'blocked' | 'inactive'
  seller_id?:       string
  person_type?:     'INDIVIDUAL' | 'COMPANY'
  days_without_sale?: number
}

export interface CustomerFinancialSummary {
  open_invoices_count:               number
  open_invoices_total_cents:         number
  paid_invoices_count:               number
  paid_invoices_total_cents:         number
  overdue_invoices_count:            number
  overdue_invoices_total_cents:      number
  overdue_paid_invoices_count:       number
  overdue_paid_invoices_total_cents: number
  last_purchase_at:                  string | null
  last_purchase_invoice_number:      string | null
  last_purchase_value_cents:         number
  highest_purchase_value_cents:      number
  highest_purchase_invoice_number:   string | null
  highest_purchase_at:               string | null
  avg_ticket_cents:                  number
  days_without_purchase:             number
  credit_limit_cents:                number
  credit_used_cents:                 number
  credit_available_cents:            number
}

export const customerService = {
  // ── Customers ────────────────────────────────────────────────────────────

  async getCustomers(filters?: CustomerFilters): Promise<PaginatedResponse<Customer>> {
    const params = new URLSearchParams()
    if (filters?.q)               params.set('q', filters.q)
    if (filters?.document)        params.set('document', filters.document)
    if (filters?.email)           params.set('email', filters.email)
    if (filters?.tag)             params.set('tag', filters.tag)
    if (filters?.active !== undefined) params.set('active', filters.active ? '1' : '0')
    if (filters?.include_default) params.set('include_default', '1')
    if (filters?.per_page)        params.set('per_page', String(filters.per_page))
    if (filters?.page)            params.set('page', String(filters.page))
    if (filters?.status)          params.set('status', filters.status)
    if (filters?.seller_id)       params.set('seller_id', filters.seller_id)
    if (filters?.person_type)     params.set('person_type', filters.person_type)
    if (filters?.days_without_sale !== undefined) params.set('days_without_sale', String(filters.days_without_sale))
    const qs = params.toString()
    const url = `/customers${qs ? `?${qs}` : ''}`

    // The backend returns { success: true, data: Customer[], meta: PaginationMeta }
    // where meta lives at the envelope level (ApiSuccess.meta), not nested inside data.
    // We use apiClient directly to capture both fields.
    const res = await apiClient.get<ApiSuccess<Customer[]> & { meta?: PaginationMeta }>(url)
    const body = res.data
    if (!body.success) throw new Error((body as unknown as { message: string }).message)
    return {
      data: body.data,
      meta: (body.meta as unknown as PaginationMeta) ?? { current_page: 1, per_page: 20, total: 0, last_page: 1 },
    }
  },

  getCustomer(uuid: string): Promise<Customer> {
    return apiGet<Customer>(`/customers/${uuid}`)
  },

  createCustomer(data: CreateCustomerRequest): Promise<Customer> {
    return apiPost<Customer, CreateCustomerRequest>('/customers', data)
  },

  updateCustomer(uuid: string, data: UpdateCustomerRequest): Promise<Customer> {
    return apiPut<Customer, UpdateCustomerRequest>(`/customers/${uuid}`, data)
  },

  deleteCustomer(uuid: string): Promise<void> {
    return apiDelete<void>(`/customers/${uuid}`)
  },

  async blockCustomer(uuid: string, reason: string): Promise<void> {
    await apiClient.patch(`/customers/${uuid}/block`, { reason })
  },

  async unblockCustomer(uuid: string): Promise<void> {
    await apiClient.patch(`/customers/${uuid}/unblock`, {})
  },

  getFinancialSummary(uuid: string): Promise<CustomerFinancialSummary> {
    return apiGet<CustomerFinancialSummary>(`/customers/${uuid}/financial-summary`)
  },

  // ── Addresses ────────────────────────────────────────────────────────────

  getAddresses(customerUuid: string): Promise<CustomerAddress[]> {
    return apiGet<CustomerAddress[]>(`/customers/${customerUuid}/addresses`)
  },

  createAddress(
    customerUuid: string,
    data: Omit<CustomerAddress, 'uuid' | 'customer_id'>,
  ): Promise<CustomerAddress> {
    return apiPost(`/customers/${customerUuid}/addresses`, data)
  },

  updateAddress(
    customerUuid: string,
    addrUuid: string,
    data: Partial<CustomerAddress>,
  ): Promise<CustomerAddress> {
    return apiPut(`/customers/${customerUuid}/addresses/${addrUuid}`, data)
  },

  deleteAddress(customerUuid: string, addrUuid: string): Promise<void> {
    return apiDelete(`/customers/${customerUuid}/addresses/${addrUuid}`)
  },

  // ── Contacts ─────────────────────────────────────────────────────────────

  getContacts(customerUuid: string): Promise<CustomerContact[]> {
    return apiGet<CustomerContact[]>(`/customers/${customerUuid}/contacts`)
  },

  createContact(
    customerUuid: string,
    data: Omit<CustomerContact, 'uuid' | 'customer_id' | 'type_label'>,
  ): Promise<CustomerContact> {
    return apiPost(`/customers/${customerUuid}/contacts`, data)
  },

  updateContact(
    customerUuid: string,
    contactUuid: string,
    data: Partial<CustomerContact>,
  ): Promise<CustomerContact> {
    return apiPut(`/customers/${customerUuid}/contacts/${contactUuid}`, data)
  },

  deleteContact(customerUuid: string, contactUuid: string): Promise<void> {
    return apiDelete(`/customers/${customerUuid}/contacts/${contactUuid}`)
  },

  // ── Tags on customer ─────────────────────────────────────────────────────

  attachTag(customerUuid: string, tagId: string): Promise<CustomerTag[]> {
    return apiPost(`/customers/${customerUuid}/tags`, { tag_id: tagId })
  },

  detachTag(customerUuid: string, tagUuid: string): Promise<void> {
    return apiDelete(`/customers/${customerUuid}/tags/${tagUuid}`)
  },

  // ── Tag management ────────────────────────────────────────────────────────

  getTags(): Promise<CustomerTag[]> {
    return apiGet<CustomerTag[]>('/customer-tags')
  },

  createTag(data: CreateCustomerTagRequest): Promise<CustomerTag> {
    return apiPost<CustomerTag, CreateCustomerTagRequest>('/customer-tags', data)
  },

  updateTag(uuid: string, data: Partial<CreateCustomerTagRequest>): Promise<CustomerTag> {
    return apiPut(`/customer-tags/${uuid}`, data)
  },

  deleteTag(uuid: string): Promise<void> {
    return apiDelete(`/customer-tags/${uuid}`)
  },
}
