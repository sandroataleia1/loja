import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'

export interface PaymentCondition {
  uuid:       string
  name:       string
  is_system:  boolean
  sort_order: number
}

export interface TenantPaymentMethod {
  uuid:       string
  name:       string
  type:       string | null
  is_system:  boolean
  sort_order: number
}

export const paymentService = {
  // ── Conditions ──────────────────────────────────────────────────────────────

  listConditions(): Promise<PaymentCondition[]> {
    return apiGet<PaymentCondition[]>('/payment-conditions')
  },

  createCondition(name: string): Promise<PaymentCondition> {
    return apiPost<PaymentCondition, { name: string }>('/payment-conditions', { name })
  },

  updateCondition(uuid: string, name: string): Promise<PaymentCondition> {
    return apiPut<PaymentCondition, { name: string }>(`/payment-conditions/${uuid}`, { name })
  },

  deleteCondition(uuid: string): Promise<void> {
    return apiDelete<void>(`/payment-conditions/${uuid}`)
  },

  // ── Methods ─────────────────────────────────────────────────────────────────

  listMethods(): Promise<TenantPaymentMethod[]> {
    return apiGet<TenantPaymentMethod[]>('/payment-methods')
  },

  createMethod(name: string): Promise<TenantPaymentMethod> {
    return apiPost<TenantPaymentMethod, { name: string }>('/payment-methods', { name })
  },

  updateMethod(uuid: string, name: string): Promise<TenantPaymentMethod> {
    return apiPut<TenantPaymentMethod, { name: string }>(`/payment-methods/${uuid}`, { name })
  },

  deleteMethod(uuid: string): Promise<void> {
    return apiDelete<void>(`/payment-methods/${uuid}`)
  },
}
