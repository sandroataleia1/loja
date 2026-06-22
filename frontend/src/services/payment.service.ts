import { apiGet, apiPost, apiPut, apiDelete } from '@/lib/api-client'

// ── Types ────────────────────────────────────────────────────────────────────

export interface PaymentCondition {
  uuid:               string
  name:               string
  type:               'a_vista' | 'parcelado' | 'entrada_parcelas' | 'variavel'
  discount_type:      'none' | 'percent' | 'fixed'
  discount_value:     number
  interest_type:      'none' | 'percent_month' | 'percent_total' | 'fixed_per_installment' | 'fixed_total'
  interest_value:     number
  fine_percent:       number
  fine_after_days:    number
  grace_days:         number
  installment_count:  number
  first_due_days:     number
  interval_days:      number
  has_entry:          boolean
  entry_percent:      number
  is_variable:        boolean
  is_active:          boolean
  is_system:          boolean
  sort_order:         number
}

export interface TenantPaymentMethod {
  uuid:                        string
  name:                        string
  type:                        string | null
  accepts_change:              boolean
  allow_installments:          boolean
  max_installments:            number
  min_installment_value_cents: number
  requires_authorization:      boolean
  integrates_financial:        boolean
  is_active:                   boolean
  is_system:                   boolean
  sort_order:                  number
}

export interface CreatePaymentMethodRequest {
  name:                        string
  type?:                       string | null
  accepts_change?:             boolean
  allow_installments?:         boolean
  max_installments?:           number
  min_installment_value_cents?: number
  requires_authorization?:     boolean
  integrates_financial?:       boolean
}

export interface CreatePaymentConditionRequest {
  name:               string
  type?:              PaymentCondition['type']
  discount_type?:     PaymentCondition['discount_type']
  discount_value?:    number
  interest_type?:     PaymentCondition['interest_type']
  interest_value?:    number
  fine_percent?:      number
  fine_after_days?:   number
  grace_days?:        number
  installment_count?: number
  first_due_days?:    number
  interval_days?:     number
  has_entry?:         boolean
  entry_percent?:     number
  is_variable?:       boolean
  is_active?:         boolean
}

export interface InstallmentPreview {
  number:        number
  due_date:      string
  amount_cents:  number
}

export interface CalculateInstallmentsResponse {
  original_amount_cents: number
  discount_cents:        number
  interest_cents:        number
  total_amount_cents:    number
  installments:          InstallmentPreview[]
}

// ── Service ──────────────────────────────────────────────────────────────────

export const paymentService = {
  // ── Conditions ──────────────────────────────────────────────────────────────

  listConditions(): Promise<PaymentCondition[]> {
    return apiGet<PaymentCondition[]>('/payment-conditions')
  },

  createCondition(data: CreatePaymentConditionRequest): Promise<PaymentCondition> {
    return apiPost<PaymentCondition, CreatePaymentConditionRequest>('/payment-conditions', data)
  },

  updateCondition(uuid: string, data: CreatePaymentConditionRequest): Promise<PaymentCondition> {
    return apiPut<PaymentCondition, CreatePaymentConditionRequest>(`/payment-conditions/${uuid}`, data)
  },

  deleteCondition(uuid: string): Promise<void> {
    return apiDelete<void>(`/payment-conditions/${uuid}`)
  },

  calculateInstallments(
    uuid: string,
    amountCents: number,
    saleDate?: string,
    installmentCount?: number,
  ): Promise<CalculateInstallmentsResponse> {
    return apiPost<CalculateInstallmentsResponse, object>(`/payment-conditions/${uuid}/calculate`, {
      amount_cents:      amountCents,
      sale_date:         saleDate,
      installment_count: installmentCount,
    })
  },

  // ── Methods ─────────────────────────────────────────────────────────────────

  listMethods(): Promise<TenantPaymentMethod[]> {
    return apiGet<TenantPaymentMethod[]>('/payment-methods')
  },

  createMethod(data: CreatePaymentMethodRequest): Promise<TenantPaymentMethod> {
    return apiPost<TenantPaymentMethod, CreatePaymentMethodRequest>('/payment-methods', data)
  },

  updateMethod(uuid: string, data: Partial<CreatePaymentMethodRequest> & { is_active?: boolean }): Promise<TenantPaymentMethod> {
    return apiPut<TenantPaymentMethod, object>(`/payment-methods/${uuid}`, data)
  },

  deleteMethod(uuid: string): Promise<void> {
    return apiDelete<void>(`/payment-methods/${uuid}`)
  },
}
