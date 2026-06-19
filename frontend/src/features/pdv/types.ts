import type { Sale } from '@/types/shared-types'
import type { PdvCartItem } from './stores/pdvCartStore'

export type PaymentTabKey = 'cash' | 'debit' | 'credit' | 'pix'

export interface ReceiptData {
  sale:     Sale
  items:    PdvCartItem[]
  payments: PendingPayment[]
}

export interface PendingPayment {
  id:            string
  tab:           PaymentTabKey
  method:        'cash' | 'debit_card' | 'credit_card' | 'pix'
  label:         string
  amountCents:   number
  installments?: number
  nsu?:          string
  authCode?:     string
  cardBrand?:    string
  reference?:    string
}
