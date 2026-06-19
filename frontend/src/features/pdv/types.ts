export type PaymentTabKey = 'cash' | 'debit' | 'credit' | 'pix'

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
